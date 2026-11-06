#!/bin/sh
# Run the hurl tests of the Marketplace module.
# Usage: sh run.sh            run all files 0*.hurl in alphabetical order
#        sh run.sh '03_*'     run only the files matching the pattern (ex: cart)
#
# Two run modes, set by run_mode in vars.env (default: safe):
#   safe : for an instance where data must not be created (production). Files whose name
#          contains _WRITE (they create data, send emails, change settings) are skipped.
#   full : for a dedicated test instance. All files are run, including the _WRITE ones.
#
# Payment: files named *_stancer_* / *_stripe_* only run when the instance uses that gateway. It is detected
# automatically (detect_payment.hurl opens payment.php like a customer), nothing to configure.
# The *_stancer_* / *_stripe_* files also call third party hosts (Stancer, 3D Secure, Stripe): the basic auth of the site must not be
# sent to them, so --user is not used for them and they set [BasicAuth] on their requests to the site only.
#
# Session: each file has its own session (a visitor, or a login done inside the file).
cd "$(dirname "$0")" || exit 1

if [ ! -f vars.env ]; then
	echo "Missing vars.env: copy vars.env.example to vars.env and fill it"
	exit 1
fi

# Load vars.env to read the run mode and the optional basic auth (hurl reads the same file for its variables)
. ./vars.env

mode="${run_mode:-safe}"
case "$mode" in
	safe|full) ;;
	*) echo "Invalid run_mode '$mode' in vars.env (use safe or full)"; exit 1 ;;
esac
echo "Run mode: $mode on $base_url"

pattern="${1:-0*}"
mkdir -p report

# --insecure: instance with a self-signed HTTPS certificate
# --user (added per file below): only if a basic auth is set in vars.env
opts="--test --insecure --variables-file vars.env --report-html report"

gateway=""
detected=""

# Detects the payment gateway of the instance (once, only when a gateway file is about to run)
detect_gateway() {
	detected=1
	detectopts="--insecure --variables-file vars.env"
	[ -n "$basic_user" ] && detectopts="$detectopts --user $basic_user:$basic_password"
	page=$(hurl $detectopts detect_payment.hurl 2>/dev/null)
	method=$(printf '%s' "$page" | grep -o 'paymentmethod=[A-Za-z0-9_]*' | head -1 | cut -d= -f2)
	case "$method" in
		stancer*) gateway=stancer ;;
		stripe*) gateway=stripe ;;
		*) gateway="$method" ;;
	esac
	echo "Payment gateway detected: ${gateway:-unknown}"
}

status=0
skipped=0
for file in $pattern.hurl; do
	case "$file" in
		*_WRITE*)
			if [ "$mode" = "safe" ]; then
				echo "Skipped $file (writes data, needs run_mode=full)"
				skipped=$((skipped + 1))
				continue
			fi
			;;
	esac
	# Gateway files: skip the ones that do not match the gateway of the instance
	wanted=""
	case "$file" in *_stancer_*) wanted=stancer ;; *_stripe_*) wanted=stripe ;; esac
	if [ -n "$wanted" ]; then
		[ -z "$detected" ] && detect_gateway
		if [ "$gateway" != "$wanted" ]; then
			echo "Skipped $file (the instance uses ${gateway:-an unknown gateway}, not $wanted)"
			skipped=$((skipped + 1))
			continue
		fi
	fi
	fileopts="$opts"
	case "$file" in
		*_stancer_*|*_stripe_*) ;;
		*) [ -n "$basic_user" ] && fileopts="$opts --user $basic_user:$basic_password" ;;
	esac
	hurl $fileopts "$file" || status=1
done
echo "Skipped files: $skipped"
exit $status
