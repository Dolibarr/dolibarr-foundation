Modification du fichier /home/dolibarr/dolistore.com/httpdocs/modules/stripe_official/views/templates/front/payment_form_card.tpl

        <!-- @CHANGE LDR -->
    {if isset($stripeError) || true}
        <div class="stripe-error-message alert alert-danger">
            <p>{$stripeError|escape:'htmlall':'UTF-8'}</p>
        </div>
    {/if}

    