			var ajaxurl = wbpu_obj.ajaxurl;
			var wp_product_update_ids = { action: 'techno_change_price_percentge'};		
			var wp_product_get_ids = { action: 'techno_change_price_product_ids'};		
			var arr = [];
		   	var opration_type='';
		   	var price_type_by_change='';
		   	var percentage='';
		   	var tc_dry_run = '';
		   	var price_rounds_point='';
			function tc_start_over() 
			{					
				jQuery('#percentge_submit').css({'opacity':0.5});
				jQuery('#percentge_submit').attr('disable',true);
				jQuery('#update_product_results_body').html('');
				jQuery('#loader').show();				
			}
			function techno_chage_price() 
			{				
				Array.prototype.chunk = function(n) {
					return (!this.length) ? [] : [this.slice(0, n)].concat(this.slice(n).chunk(n));
				};
				jQuery('.techno-progress').attr('value',0);
				if(arr.length == 0)
				{
					percentage=jQuery("#percentage").val();	
					if(percentage > 0)
					{	
						opration_type = jQuery("input[name='price_change_type']:checked").val();	
						price_type_by_change = jQuery("input[name='price_type_by_change']:checked").val();	
						price_rounds_point = (jQuery("#price_rounds_point").is(":checked")) ? 'true' : 'false';
						tc_dry_run = (jQuery("#tc_dry_run").is(":checked")) ? 'true' : 'false';
						if(jQuery("input[name='price_change_method']:checked").val()=='by_categories')
						{
							if(jQuery('#techno_product_select').val() !== null && jQuery('#techno_product_select').val().length > 0){
								tc_start_over();
								wp_product_get_ids['cat_ids'] = jQuery('#techno_product_select').val();	
								wp_product_get_ids['nonce'] = wbpu_obj.wporg_product_ids;			
								jQuery.post( ajaxurl, wp_product_get_ids, function(res_cat) 
								{
									arr = JSON.parse(res_cat);
									arr = arr.chunk(5);
									recur_loop();
									jQuery('.techno-progress').attr('max',arr.length);
								});
							}
							else{
								alert('Please select a Category...!!');
							}			
						}
						else{
							if(jQuery('#add_products').val() != null){
								arr = jQuery('#add_products').val();
									arr = arr.chunk(5);
								tc_start_over();
								recur_loop(); 
								jQuery('.techno-progress').attr('max',arr.length);
								return false;
							}
							else{
								alert('Please select a Product...!!');								
							}
						}
					}			
					else
					{
						alert('Please provide a Amount more-than Zero...!!');
					}
				}				
			}	
			var recur_loop = function(i) 
			{
			    var num = i || 0; 
			    if(num < arr.length) 
			    {
			        wp_product_update_ids['product_id'] = arr[num];
			        wp_product_update_ids['opration_type'] = opration_type;
			        wp_product_update_ids['price_type_by_change'] = price_type_by_change;
			        wp_product_update_ids['percentage'] = percentage;
			        wp_product_update_ids['price_rounds_point'] = price_rounds_point;
			        wp_product_update_ids['tc_dry_run'] = tc_dry_run;
			        wp_product_update_ids['tc_req_count'] = num;
			        wp_product_update_ids['nonce'] = wbpu_obj.wporg_product_update_ids;
				   	jQuery.post( ajaxurl, wp_product_update_ids, function(response) 
				   	{
				   		jQuery('#update_product_results').show();
				   		var count=num+1;
			        	recur_loop(num+1);
				   		jQuery('.techno-progress').attr('value',count);
				   		jQuery('#update_product_results_body').append(response);
					});  
			    }
			    else
			    {
			    	arr = [];
					jQuery('#loader').hide();
					if(tc_dry_run=='true'){
						alert('Dry Run Complete...!!');
					}
					else{
						jQuery('#techno_product_select').val('');
						jQuery("#percentage").val('');	
						jQuery('#techno_product_select').multiselect('refresh');
						if(jQuery('.chosen-select').length > 0){
							jQuery('.search-choice-close').trigger('click');
						}
						alert('Operation Complete...!!');
					}
					jQuery('#percentge_submit').css({'opacity':''});
					jQuery('#percentge_submit').removeAttr('disable');
			    }
			};
			jQuery(document).ready(function(jQuery) 
			{
				jQuery('#method_'+jQuery('input[name="price_change_method"]').val()).show();
				jQuery('input[name="price_change_method"]').change(function(e)
				{
					jQuery('.method_aria_tc').hide();
					jQuery('#method_'+jQuery(this).val()).show();
				});
				var nonce = wbpu_obj.techno_products_nonce;
				jQuery("#techno_product_select").multiselect({enableClickableOptGroups: true,enableCollapsibleOptGroups: true,enableFiltering: true,includeSelectAllOption: true });
	            jQuery("select.chosen-select").select2({
			        ajax: {
					    url: ajaxurl,
					    dataType: 'json',
					    delay: 250,
					    data: function (params) {
					      	return {
					        	s: params.term,
										nonce: nonce,
					        	action: 'techno_get_products',
					        	page: params.page || 1
					      	};
					    },
					    processResults: function (data, params) {
					      	params.page = params.page || 1;
						    return {
						        results: data.results,
						        pagination: {
						            more: (params.page * 50) < data.count_filtered
						        }
						    };
					    },
					    cache: true
					},				
			        placeholder: "Select Products...",
			        width: "90%",
	  				minimumInputLength: 0,
					templateResult: formatRepo,
					templateSelection: formatRepoSelection
			    });


					// Handle Select All Checkbox Change
    jQuery("#select-all-checkbox").change(function() {
        if (this.checked) {
            // Get currently visible options
            var visibleOptions = jQuery("#add_products").data('select2').$results.find(".select2-results__option[aria-selected='false']");
						console.log("visibleOptions: ",visibleOptions);
            visibleOptions.each(function() {
                var optionData = jQuery(this).data('data');
                if (optionData) {
                    jQuery("#add_products").append(new Option(optionData.text, optionData.id, true, true));
                }
            });
            jQuery("#add_products").trigger('change');
        } else {
            // Deselect all currently visible options
						jQuery("#add_products").val(null).trigger("change");
        }
    });

    // Function to check and update the "Select All" checkbox status
    function checkSelectAll() {
        var visibleOptions = jQuery("#add_products").data('select2').$results.find(".select2-results__option");
        var allSelected = true;
        visibleOptions.each(function() {
            if (jQuery(this).attr('aria-selected') === 'false') {
                allSelected = false;
            }
        });
				jQuery("#select-all-checkbox").prop("checked", allSelected);
    }

    // Listen to select and unselect events to update "Select All" checkbox status
    jQuery("#add_products").on('select2:select select2:unselect', function() {
        checkSelectAll();
    });


			  	jQuery("#percentage").keypress(function(e) 
			  	{
					if (e.keyCode === 46 && this.value.split('.').length === 2)
					{
						return false;
					}
			   	});
			   	jQuery('div.techno_main_tabs').click(function(e){
			   		jQuery('.techno_main_tabs').removeClass('active');
			   		jQuery(this).addClass('active');
					jQuery('.techno_tabs').hide();
					jQuery('.'+this.id).show();
				});
				if(window.location.hash)
			  	{
				    var tab_active=window.location.hash.substring(1);
				    jQuery("#tab_"+tab_active).trigger('click');   
			  	}
			});			
			function formatRepo (repo) {
			  if (repo.loading) {
			    return repo.text;
			  }
			  var $container = jQuery("<div class='select2-result-repository clearfix'><div class='select2-result-repository__meta'><div class='select2-result-repository__title'>"+repo.text+"</div></div></div>");
			  return $container;
			}
			function formatRepoSelection (repo) {
			  return repo.name || repo.text;
			}