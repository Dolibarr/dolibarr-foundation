
//global variables
var combinations = new Array();
var selectedCombination = new Array();
var globalQuantity = new Number;
var colors = new Array();

//check if a function exists
function function_exists(function_name)
{
	if (typeof function_name == 'string')
		return (typeof window[function_name] == 'function');
	return (function_name instanceof Function);
}

//execute oosHook js code
function oosHookJsCode()
{
	for (var i = 0; i < oosHookJsCodeFunctions.length; i++)
	{
		if (function_exists(oosHookJsCodeFunctions[i]))
		setTimeout(oosHookJsCodeFunctions[i]+'()', 0);
	}	
}

//add a combination of attributes in the global JS sytem
function addCombination(idCombination, arrayOfIdAttributes, quantity, price, ecotax, id_image, reference)
{
	globalQuantity += quantity;

	var combination = new Array();
	combination['idCombination'] = idCombination;
	combination['quantity'] = quantity;
	combination['idsAttributes'] = arrayOfIdAttributes;
	combination['price'] = price;
	combination['ecotax'] = ecotax;
	combination['image'] = id_image;
	combination['reference'] = reference;
	combinations.push(combination);
}

// search the combinations' case of attributes and update displaying of availability, prices, ecotax, and image
function findCombination(firstTime)
{
	//create a temporary 'choice' array containing the choices of the customer
	var choice = new Array();
	$('div#attributes select').each(function(){
		choice.push($(this).val());
	});
	var nbAttributesEquals = 0;
	//testing every combination to find the conbination's attributes' case of the user
	
	for (combination in combinations)
	{
		//verify if this combinaison is the same that the user's choice
		nbAttributesEquals = 0;
		for (idAttribute in combinations[combination]['idsAttributes'])
		{
			//ie6 bug fix
			if (idAttribute != 'indexOf'){
				//if this attribute has been choose by user
				if (in_array(combinations[combination]['idsAttributes'][idAttribute], choice))
				{
					//we are in a good way to find the good combination !
					nbAttributesEquals++;
				}
			}
		}

		if (nbAttributesEquals == choice.length)
		{
			//combination of the user has been found in our specifications of combinations (created in back office)
			selectedCombination['unavailable'] = false;
			selectedCombination['reference'] = combinations[combination]['reference'];
			$('#idCombination').val(combinations[combination]['idCombination']);

			//get the data of product with these attributes
			quantityAvailable = combinations[combination]['quantity'];
			selectedCombination['price'] = combinations[combination]['price'];
			if (combinations[combination]['ecotax'])
				selectedCombination['ecotax'] = combinations[combination]['ecotax'];
			else
				selectedCombination['ecotax'] = default_eco_tax;
			
			//show the large image in relation to the selected combination
			if (combinations[combination]['image'] && combinations[combination]['image'] != -1)
				displayImage( $('#thumb_'+combinations[combination]['image']).parent() );
			
			//update the display
			updateDisplay();

			if(typeof(firstTime) != 'undefined' && firstTime)
				refreshProductImages(0);
			else
				refreshProductImages(combinations[combination]['idCombination']);
			//leave the function because combination has been found
			return;
		}
	}
	//this combination doesn't exist (not created in back office)
	selectedCombination['unavailable'] = true;
	updateDisplay();
}

function updateColorSelect(id_attribute)
{
	if (id_attribute == 0)
	{
		refreshProductImages(0);
		return ;
	}
	// Visual effect
	$('#color_'+id_attribute).fadeTo('fast', 1, function(){	$(this).fadeTo('slow', 0, function(){ $(this).fadeTo('slow', 1, function(){}); }); });
	// Attribute selection
	$('#group_'+id_color_default+' option[value='+id_attribute+']').attr('selected', 'selected');
	$('#group_'+id_color_default+' option[value!='+id_attribute+']').removeAttr('selected');
	findCombination();
}

//update display of the availability of the product AND the prices of the product
function updateDisplay()
{
	if (!selectedCombination['unavailable'] && quantityAvailable > 0)
	{
		//show the choice of quantities
		$('#quantity_wanted_p:hidden').show('slow');
		
		//show the "add to cart" button ONLY if it was hidden
		$('#add_to_cart:hidden').fadeIn(600);
		
		//hide the hook out of stock
		$('#oosHook').hide();
		
		//availability value management
		if (availableNowValue != '')
		{
			//update the availability statut of the product
			$('#availability_value').removeClass('warning-inline');
			$('#availability_value').text(availableNowValue);
			$('#availability_statut:hidden').show();
		}
		else
		{
			//hide the availability value
			$('#availability_statut:visible').hide();
		}
		
		//'last quantities' message management
		if (quantityAvailable <= maxQuantityToAllowDisplayOfLastQuantityMessage && !allowBuyWhenOutOfStock)
		{
			//display the 'last quantities' message
			$('#last_quantities').show('slow');
		}
		else
		{
			//hide the 'last quantities' message
			$('#last_quantities').hide('slow');
		}
	
		//display the quantities of pieces (only if allowed)
		if (quantitiesDisplayAllowed)
		{
			$('#pQuantityAvailable:hidden').show('slow');
			$('#quantityAvailable').text(quantityAvailable); 
			if(quantityAvailable < 2)
			{
				$('#quantityAvailableTxt').show();
				$('#quantityAvailableTxtMultiple').hide();
			}
			else
			{
				$('#quantityAvailableTxt').hide();
				$('#quantityAvailableTxtMultiple').show();
			}
		}
	}
	else
	{
		//show the hook out of stock
		$('#oosHook').show();
		if ($('#oosHook').length > 0 && function_exists('oosHookJsCode'))
			oosHookJsCode();
		
		//hide 'last quantities' message if it was previously visible
		$('#last_quantities:visible').hide('slow');

		//hide the quantity of pieces if it was previously visible
		$('#pQuantityAvailable:visible').hide('slow');
		
		//hide the choice of quantities
		if (!allowBuyWhenOutOfStock)
			$('#quantity_wanted_p:visible').hide('slow');
		
		//display that the product is unavailable with theses attributes
		if (!selectedCombination['unavailable'])
			$('#availability_value').text(doesntExistNoMore + (globalQuantity > 0 ? ' ' + doesntExistNoMoreBut : '')).addClass('warning-inline');
		else
			$('#availability_value').text(doesntExist).addClass('warning-inline');
		$('#availability_statut:hidden').show();

		
		//show the 'add to cart' button ONLY IF it's possible to buy when out of stock AND if it was previously invisible
		if (allowBuyWhenOutOfStock && !selectedCombination['unavailable'])
		{
			$('#add_to_cart:hidden').fadeIn(600);

			if (availableLaterValue != '')
			{
				$('#availability_value').text(availableLaterValue);
				$('p#availability_statut:hidden').show('slow');
			}
			else
				$('p#availability_statut:visible').hide('slow');			
		}
		else
		{
			$('#add_to_cart:visible').fadeOut(600);
			$('p#availability_statut:hidden').show('slow');
		}
	}
	
	//update display of the the prices in relation to tax, discount, ecotax, and currency criteria
	if (!selectedCombination['unavailable'])
	{
		var attribut_price_tmp = selectedCombination['price'];

		var tax = noTaxForThisProduct ? 1 : ((taxRate / 100) + 1);

		if (noTaxForThisProduct)
			attribut_price_tmp /= tax;

		if (selectedCombination['reference'])
		{
			$('#product_reference span').text(selectedCombination['reference']);
			$('#product_reference:hidden').show();
		}
		else
			$('#product_reference:visible').hide('slow');

		var productPriceWithoutReduction2 = (attribut_price_tmp + productPriceWithoutReduction) * currencyRate;
		
		if (reduction_from != reduction_to && (currentDate > reduction_to || currentDate < reduction_from))
			var priceReduct = 0;
		else
			var priceReduct = productPriceWithoutReduction2 / 100 * parseFloat(reduction_percent) + (reduction_price * currencyRate);
		var priceProduct = productPriceWithoutReduction2 - priceReduct;
		var productPricePretaxed = (productPriceWithoutReduction2 - priceReduct) / tax;

		if (displayPrice == 1)
		{
			priceProduct = productPricePretaxed;
			productPriceWithoutReduction2 /= tax;
		}

		if (group_reduction)
			priceProduct *= group_reduction;
		$('#our_price_display').text(formatCurrency(priceProduct, currencyFormat, currencySign, currencyBlank));
		$('#pretaxe_price_display').text(formatCurrency(productPricePretaxed, currencyFormat, currencySign, currencyBlank));
		$('#old_price_display').text(formatCurrency(productPriceWithoutReduction2, currencyFormat, currencySign, currencyBlank));
		$('#ecotax_price_display').text(formatCurrency(selectedCombination['ecotax'], currencyFormat, currencySign, currencyBlank));
	}
}

//update display of the large image
function displayImage(domAAroundImgThumb)
{
    if (domAAroundImgThumb.attr('href'))
    {
        var newSrc = domAAroundImgThumb.attr('href').replace('thickbox','large');
        if ($('#bigpic').attr('src') != newSrc)
		{ 
            $('#bigpic').fadeOut('fast', function(){
                $(this).attr('src', newSrc).show();
                if (typeof(jqZoomEnabled) != 'undefined' && jqZoomEnabled)
	                $(this).attr('alt', domAAroundImgThumb.attr('href'));
            });
        }
        $('#views_block li a').removeClass('shown');
        $(domAAroundImgThumb).addClass('shown');
    }
}

// Serialscroll exclude option bug ?
function serialScrollFixLock(event, targeted, scrolled, items, position)
{
	serialScrollNbImages = $('#thumbs_list li:visible').length;
	serialScrollNbImagesDisplayed = 3;
	
	var leftArrow = position == 0 ? true : false;
	var rightArrow = position + serialScrollNbImagesDisplayed >= serialScrollNbImages ? true : false;
	
	$('a#view_scroll_left').css('cursor', leftArrow ? 'default' : 'pointer').css('display', leftArrow ? 'none' : 'block').fadeTo(0, leftArrow ? 0 : 1);		
	$('a#view_scroll_right').css('cursor', rightArrow ? 'default' : 'pointer').fadeTo(0, rightArrow ? 0 : 1).css('display', rightArrow ? 'none' : 'block');
	return true;
}

// Change the current product images regarding the combination selected
function refreshProductImages(id_product_attribute)
{
	$('#thumbs_list_frame').scrollTo('li:eq(0)', 700, {axis:'x'});
	$('#thumbs_list li').hide();
	id_product_attribute = parseInt(id_product_attribute);

	if (typeof(combinationImages) != 'undefined' && typeof(combinationImages[id_product_attribute]) != 'undefined')
	{
		for (var i = 0; i < combinationImages[id_product_attribute].length; i++)
			$('#thumbnail_' + parseInt(combinationImages[id_product_attribute][i])).show();
	}
	$('#thumbs_list_frame').width((parseInt(($('#thumbs_list_frame >li').width())* i) + 3) + 'px'); //  Bug IE6, needs 3 pixels more ?
	$('#thumbs_list').trigger('goto', 0);
	serialScrollFixLock('', '', '', '', 0);// SerialScroll Bug on goto 0 ?
}

//To do after loading HTML
$(document).ready(function()
{
	//init the serialScroll for thumbs
	$('#thumbs_list').serialScroll({
		items:'li:visible',
		prev:'a#view_scroll_left',
		next:'a#view_scroll_right',
		axis:'x',
		offset:0,
		start:0,
		stop:true,
		onBefore:serialScrollFixLock,
		duration:700,
		step: 2,
		lazy: true,
		lock: false,
		force:false,
		cycle:false
	});
	
	$('#thumbs_list').trigger('goto', 1);// SerialScroll Bug on goto 0 ?
	$('#thumbs_list').trigger('goto', 0);

	//hover 'other views' images management
	$('#views_block li a').hover(
		function(){displayImage($(this));},
		function(){}
	);
	
	//set jqZoom parameters if needed
	if (typeof(jqZoomEnabled) != 'undefined' && jqZoomEnabled)
	{
		$('img.jqzoom').jqueryzoom({
			xzoom: 200, //zooming div default width(default width value is 200)
			yzoom: 200, //zooming div default width(default height value is 200)
			offset: 21 //zooming div default offset(default offset value is 10)
			//position: "right" //zooming div position(default position value is "right")
		});
	}

	//add a link on the span 'view full size' and on the big image
	$('span#view_full_size, div#image-block img').click(function(){
		$('#views_block li a.shown').click();
	});

	//catch the click on the "more infos" button at the top of the page
	$('div#short_description_block p a.button').click(function(){
		$('#more_info_tab_more_info').click();
		$.scrollTo( '#more_info_tabs', 1200 );
	});

	// Hide the customization submit button and display some message
	$('p#customizedDatas input').click(function() {
		$('p#customizedDatas input').hide();
		$('p#customizedDatas').append('<img src="' + img_ps_dir + 'loader.gif" alt="" /> ' + uploading_in_progress);
	});

	//init the price in relation of the selected attributes
	if (typeof productHasAttributes != 'undefined' && productHasAttributes)
		findCombination(true);

	//
	$('a#resetImages').click(function() {
		updateColorSelect(0);
	});
});

function saveCustomization()
{
	$('#quantityBackup').val($('#quantity_wanted').val());
	$('body select[@id^="group_"]').each(function() {
		$('#customizationForm').attr('action', $('#customizationForm').attr('action') + '&' + this.id + '=' + parseInt(this.value));
	});
	$('#customizationForm').submit();
}
