function buscarEndereco(host,quale) {

	new Ajax.Request(host + 'paybras_cep.php?cep=' + document.getElementById(quale+':postcode').value.replace(/\+/g, ''), {
            method:'get',
            onSuccess: function(respostaCEP) {
				r = respostaCEP.responseText;
				
				street_1 = r.substring(0, (i = r.indexOf(':')));
				document.getElementById(quale+':street1').value = unescape(street_1.replace(/\+/g," "));

				r = r.substring(++i);
				street_4 = r.substring(0, (i = r.indexOf(':')));
				document.getElementById(quale+':street4').value = unescape(street_4.replace(/\+/g," "));

				r = r.substring(++i);
				city = r.substring(0, (i = r.indexOf(':')));
				document.getElementById(quale+':city').value = unescape(city.replace(/\+/g," "));

				r = r.substring(++i);
				region = r.substring(0, (i = r.indexOf(':')));

				r = r.substring(++i);
				
				regionID = r.substring(0, 3);
				
				regionSelect = region;
				region = region.replace(/\+/g," ");
				
				$$('select[name="'+quale+'[region_id]"] option').each(function(element) {
					if(element.value == regionID){
						element.selected = true;
					}
				});
				
				setTimeout(function() { document.getElementById(quale+':street2').focus(); }, 1);
			}
	});
	
};