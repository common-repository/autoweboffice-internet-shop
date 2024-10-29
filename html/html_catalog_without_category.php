<style>
.category_block {
	margin-bottom: 22px;
}
.goods_block {
	margin: 14px 0px 0px 0px;
}
.sub_category_block {
	background-color: #F2F1EF;
	padding: 8px;
	margin: 14px 0px 14px 0px;
	border-radius: 6px;
	
	-webkit-column-count:2;  
    -moz-column-count:2;  
    column-count:2; 
}
.category_title {
	font-size: 1.5em;
	text-decoration: none !important;
}
.category_d {
	height: 1px;
	border-bottom: 1px dashed #ccc;
	margin: 10px 0px 24px 0px;
}
.category_more {
	text-align: right;
}
.category_more a {
	background-color: #DADFE1;
	font-size: 0.9em;
	padding: 4px 8px 4px 8px;
	border-radius: 3px;
}
</style>
<?php
		
    $html_catalog .= '<div class="category_block">';
	
		
													
		$awo_goods = $wpdb->get_results("SELECT * 
											FROM `".$this->tbl_awo_goods."` 
											WHERE deleted=0 
											AND not_sold=0 
											AND awo_not_show=0
											".$where."
											ORDER BY  `creation_date` DESC  
											LIMIT 9");
		
		
		
		
			
			
			
			// Выводим товары для категории
			$html_catalog .= '<div align="center" class="goods_block">';
			
			foreach ($awo_goods as $goods)
			{
				$html_goods = '';
				$id_goods = $goods->id_goods;
	
				$goods_name = htmlspecialchars(trim($goods->goods));
						
				$url_page = $goods->url_page;
				$image = $goods->image;
				
				$goods_url = '/?page_id='.get_query_var('page_id').'&page='.get_query_var('page').'&id_goods='.$id_goods;
				
				// Блок товара
				$html_goods .= '<div style="min-width: 150px; margin: 6px 16px 6px 16px; display: inline-block; width: '.$awo_catalog_goods_width.'px;">';
				
				
				// Изображение товара
				if(trim($url_page) != '')
				{
					$html_goods .= '<div><a href="'.$url_page.'" title="Узнать подробней о '.$goods_name.'">';
				}
				// Если есть данные по изображению товара
                if(trim($image) != '')
                {
                    // Теперь надо узнать в какой папке лежит изображение
                    if($this->is_url_exist('https://'.$awo_storesId.'.autoweboffice.ru/userdata/'.$awo_storesId.'/goods/'.$image) )
                    {
                        $image_url = 'https://'.$awo_storesId.'.autoweboffice.ru/userdata/'.$awo_storesId.'/goods/'.$image;
                    }
                    else
                    {
                        $image_url = 'https://'.$awo_storesId.'.autoweboffice.ru/images/goods/pics/'.$image;
                    }
                }
                else
                {
                    $image_url = '/wp-content/plugins/autoweboffice-internet-shop/img/cap/goods.png';
                }
				
				$html_goods .= '<img style="vertical-align: top; max-height: 150px; max-width: 150px;" src="'.$image_url.'" alt="'.$goods_name.'" title="'.$goods_name.'">';
				
				if(trim($url_page) != '')
				{
					$html_goods .= '</a></div>';
				}
				
				
				
				// Название товара  min-height:40px; padding: 12px 0px 8px 0px;
				$html_goods .= '<div style="min-height:40px;">';
				if(trim($url_page) != '')
				{
					$html_goods .= '<a href="'.$url_page.'" title="Узнать подробней о '.$goods_name.'">';
				}
				$html_goods .= '<b>'.$goods_name.'</b>';
				if(trim($url_page) != '')
				{
					$html_goods .= '</a>';
				}
				$html_goods .= '</div>';
				
				
				//Цена товара
				$price = $goods->price;
				$html_goods .= '<div style="width: 100%; height: 40px; line-height: 40px; overflow: hidden; display: block; border: 1px dashed #d2d0cb; text-align: center; font-size: 17px; border-radius: 5px; margin-bottom: 15px;}">'.$price.'</div>';
				$html_goods .= '<div align="center" style="padding:0px 0px 20px 0px;"><input style="width: 172px; padding: 5px;" type="submit" class="awo_add_to_cart" id="'.$id_goods.'" value="'.$awo_catalog_settings_submit_value.'" title="'.$goods_name.' - '.$awo_catalog_settings_submit_value.'"></div>';
				
				
				
				$html_goods .= '</div>';
				$html_catalog .= $html_goods;
			}
			
		$html_catalog .= "</div><div class='category_d'></div>";
	
        
        
    $html_catalog .= "</div>";
	
	
	
		
		
		
		
		
	
	

?>