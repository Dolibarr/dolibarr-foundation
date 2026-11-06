# Hurl tests of the Marketplace module

HTTP tests run with Hurl against a running Dolibarr instance.

## Setup

1. Install hurl: `scoop install hurl`
2. Copy `vars.env.example` to `vars.env` and fill it. `vars.env` is ignored by git.
   - `base_url`: host serving the template website (ex: `https://marketplace.local`).
   - `basic_user` / `basic_password`: optional HTTP basic auth in front of the site; leave empty if none (`run.sh` then passes `--user` to hurl only when set).
   - `customer_*`: dedicated test account of the website (email + password). It is also used for the vendor tests.

## Run

```sh
sh run.sh          # all files 0*.hurl, in alphabetical order
sh run.sh '03_*'   # only one phase (here: cart)
```

## Run modes (`run_mode` in `vars.env`)

| Mode | Use on | Behavior |
|---|---|---|
| `safe` (default) | production, or any instance where data must not be created | files with `_WRITE` in their name are skipped |
| `full` | dedicated test instance | all files run, including the ones that create data, send emails or change settings |

## File naming rules


- `*_WRITE*`: the test creates data, sends an email or changes a setting. Skipped in `safe` mode.
- `*_stancer_*` / `*_stripe_*`: payment files for one gateway. They only run when the instance uses that gateway.
- Each file has its own session (visitor, or a login done inside the file itself).

The HTML report is written to `report/`.

## Fixtures

`fixtures/` holds the files uploaded by the vendor tests (fake module zip named `module_hurltest-1.0.0.zip`, PNG images). The module is not installable.
