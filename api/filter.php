<?php
class api_filter {

    function filterForForm($data) {
        $data 	= isset($data) ? str_replace("\n", '', stripslashes(stripslashes($data))) : '';

	//	$data  = is_integer(stripos($data, 'Lorem ipsum dolor sit')) ? $default_content_if_empty : $return[$row['id']]['content'];
					
		//$data .= "\n";
					
		// fix old <img src=\"apa.jpg"\">
		$data = str_ireplace('gif%22\">', 'gif">', $data);
		$data = str_ireplace('jpg%22\">', 'jpg">', $data);
		$data = str_ireplace('jpeg%22\">', 'jpeg">', $data);
					
        $data = str_ireplace('?>', '>', $data);
        $data = str_ireplace('?="">', '>', $data);

		// Hide divs
		$data = str_ireplace('<div', '[div', $data);
		$data = str_ireplace('</div>', '[/div]', $data);
					
		// FILTERING
			$data = str_replace('endag.mediatroop.se', 'www.lifeportalen.se', $data);
					
 		//	$data = str_replace('&nbsp;', '', $data);
       		$data = preg_replace('/(&nbsp;)+/', ' ', $data);
         		
       		$data = str_replace('<br/>', '<br>', $data);
         
       		$data = strip_tags($data, '<br><p><b><strong><i><u><ol><ul><li><hr><img><table><tr><td><th><tbody><h1><h2><h3><h4><a><script><div><iframe>');
						
	// disable the bottom margin/padding which the <OL> and <UL> tags make (this padding/margin appears to be an extra <br>)
$data = preg_replace(array('/<ol(.*?)>/'), array('<ol style="padding-bottom:0; padding-top:0; margin-bottom:0; margin-top:0;">'), $data);
$data = preg_replace(array('/<OL(.*?)>/'), array('<OL style="padding-bottom:0; padding-top:0; margin-bottom:0; margin-top:0;">'), $data);
$data = preg_replace(array('/<ul(.*?)>/'), array('<ul style="padding-bottom:0; padding-top:0; margin-bottom:0; margin-top:0;">'), $data);
$data = preg_replace(array('/<UL(.*?)>/'), array('<UL style="padding-bottom:0; padding-top:0; margin-bottom:0; margin-top:0;">'), $data);
						
   // Filter span tags
   $data = preg_replace(array('/<SPAN class=largertext>/'), array('[span class="largertext"]'), $data);
            $data = preg_replace(array('/<span class=largertext>/'), array('[span class="largertext"]'), $data);
			            
            $data = preg_replace(array('/<SPAN class=colorized>/'), array('[span class="colorized"]'), $data);
            $data = preg_replace(array('/<span class=colorized>/'), array('[span class="colorized"]'), $data);
			          
            $data = preg_replace(array('/<span(.*?)>/'), array('<span>'), $data);
            $data = preg_replace(array('/<SPAN(.*?)>/'), array('<span>'), $data);
			          
            // Enable allowed spans
            $data = preg_replace(array('/\[span class="largertext"\]/'), array('<span class=largertext>'), $data);
            $data = preg_replace(array('/\[span class="colorized"\]/'), array('<span class=colorized>'), $data);
            
			// remove <P> tag
			$data = preg_replace(array('/<p(.*?)>/'), array(''), $data);
			$data = preg_replace(array('/<P(.*?)>/'), array(''), $data);
				
			// replace </P> tags 
			$data = str_ireplace('</p>', '<br/>', $data);
			$data = str_ireplace('</P>', '<br/>', $data);
					
			// remove all parameters to allowed tags
			$data = preg_replace(array('/<(p|P) (.*?)>/'), array('<p>'), $data);
			$data = preg_replace(array('/<(b|B) (.*?)>/'), array('<b>'), $data);
			$data = preg_replace(array('/<(strong|STRONG) (.*?)>/'), array('<strong>'), $data);
			$data = preg_replace(array('/<(i|I) (.*?)>/'), array('<i>'), $data);
			$data = preg_replace(array('/<(u|U) (.*?)>/'), array('<u>'), $data);
			$data = preg_replace(array('/<(ol|OL) (.*?)>/'), array('<ol>'), $data);
			$data = preg_replace(array('/<(ul|UL) (.*?)>/'), array('<ul>'), $data);
			$data = preg_replace(array('/<(li|LI) (.*?)>/'), array('<li>'), $data);
			$data = preg_replace(array('/<(hr|HR) (.*?)>/'), array('<hr>'), $data);
			//$data = preg_replace(array('/<(table|TABLE) (.*?)>/'), array('<table>'), $data);
			$data = preg_replace(array('/<(tr|TR) (.*?)>/'), array('<tr>'), $data);
			//$data = preg_replace(array('/<(td|TD) (.*?)>/'), array('<td>'), $data);
			$data = preg_replace(array('/<(th|TH) (.*?)>/'), array('<th>'), $data);
			$data = preg_replace(array('/<(tbody|TBODY) (.*?)>/'), array('<tbody>'), $data);
				
			// enable javascript
			$data = str_ireplace('[script', '<script', urldecode($data));
			$data = str_ireplace('[/script]', '</script>', $data);
			$data = str_replace('&gt;', '>', $data);
				
			// enable div
			$data = str_ireplace('[div', '<div', urldecode($data));
			$data = str_ireplace('[/div]', '</div>', $data);
			$data = str_replace('&gt;', '>', $data);
						
						// replace $config['http_root']
					//	$data = str_replace('http://www.lifeportalen.se/', $config['http_root'], $data);
        return $data;
    }
}
