<?php
/*
 * Файл отвечающий за запуск плагина
 * Plugin Name: AutoWebOffice Internet Shop
 * Plugin URI: http://wordpress.org/plugins/autoweboffice-internet-shop/
 * Description: Создание интернет магазина на базе платформы WordPress интегрированного с сервисом АвтоОфис
 * Version: 0.36
 * Author: Alexander Kruglov (zakaz@autoweboffice.com)
 * Author URI: http://autoweboffice.com/
 */
                    

// Если не существует папки с плагином, то прекращаем работу
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) 
{ 
	die('You are not allowed to call this page directly.'); 
}
 
if (!class_exists('AutowebofficeInternetShop')) 
{
	// Основной класс плагина
	class AutowebofficeInternetShop 
	{
		// Хранение внутренних данных
		public $data = array();
		
		// Конструктор объекта
		// Инициализация основных переменных
		function AutowebofficeInternetShop()
		{
			global $wpdb;
		
			## Объявляем константу инициализации нашего плагина
			define('AutowebofficeInternetShop', true);
			
			## Название файла нашего плагина 
			$this->plugin_name = plugin_basename(__FILE__);
			
			## URL адресс для нашего плагина
			$this->plugin_url = trailingslashit(WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));
			
			## Таблицы используемые плагином
			## обязательно должна быть глобально объявлена перменная $wpdb
			$this->tbl_awo_goods   		= $wpdb->prefix.'awo_goods'; // Товары
			$this->tbl_awo_settings   	= $wpdb->prefix.'awo_settings'; // Настройки
			$this->tbl_awo_goods_category = $wpdb->prefix.'awo_goods_category'; // Категории товаров

			
			## Функция которая исполняется при активации плагина
			register_activation_hook($this->plugin_name, array(&$this, 'activate'));
			
			## Функция которая исполняется при деактивации плагина
			register_deactivation_hook($this->plugin_name, array(&$this, 'deactivate'));
			
			##  Функция которая исполняется удалении плагина
			register_uninstall_hook($this->plugin_name, array(&$this, 'uninstall'));
			
			## Для обработки AJAX запроса
			## !Важно не забыть повесить эти 2 хука. Дабы wp не отправил 0 или пустой ответ
			
			// Наполнение Корзины товарами
			add_action("wp_ajax_ajax_add_goods_to_order", array(&$this, "ajax_add_goods_to_order"));
			add_action("wp_ajax_nopriv_ajax_add_goods_to_order", array(&$this, "ajax_add_goods_to_order"));
			
			// Передача данных о по заказу в АвтоОфис 
			add_action("wp_ajax_ajax_empty_cart", array(&$this, "ajax_empty_cart"));
			add_action("wp_ajax_nopriv_ajax_empty_cart", array(&$this, "ajax_empty_cart"));
			
			// Для получения текста сообще
			add_action("wp_ajax_ajax_show_message", array(&$this, "ajax_show_message"));
			add_action("wp_ajax_nopriv_ajax_show_message", array(&$this, "ajax_show_message"));
			
			// Для получения текста сообще
			add_action("wp_ajax_ajax_delete_from_cart", array(&$this, "ajax_delete_from_cart"));
			add_action("wp_ajax_nopriv_ajax_delete_from_cart", array(&$this, "ajax_delete_from_cart"));
			
			add_action('init', array(&$this, "awo_session_start")); // Включаем возможность использования Сессий

			// Подключаем необходимые виджеты
			// add_action('widgets_init', array($this, 'include_widgets'));
			
			// Если мы в адм. интерфейсе
			if (is_admin())
			{
				// Добавляем стили и скрипты
				add_action('wp_print_scripts', array(&$this, 'admin_load_scripts'));
				add_action('wp_print_styles', array(&$this, 'admin_load_styles'));
				
				// Добавляем меню для плагина
				add_action('admin_menu', array(&$this, 'admin_generate_menu'));
				
			} 
			else 
			{
				// Добавляем стили и скрипты
				add_action('wp_print_scripts', array(&$this, 'site_load_scripts'));
				add_action('wp_print_styles', array(&$this, 'site_load_styles'));
				
				// Разрешаем использование Шоткодов в виджетах
				add_filter('widget_text', 'do_shortcode'); // Стандартный текстовый виджет
				
				// Регистрируем Шоткоды
				add_shortcode('awo_link_to_order', array (&$this, 'get_link_to_order')); // Кнопка Добавить в корзину
				add_shortcode('awo_link_to_single_order', array (&$this, 'get_link_to_single_order')); // Кнопка Заказать прямо сейчас
				add_shortcode('awo_cart_info_shot', array (&$this, 'get_cart_info_shot')); // Вывод краткой информации по товарам лежащим в Корзине заказа
				add_shortcode('awo_subscribe_form', array (&$this, 'get_subscribe_form')); // Вывод формы подписки на рассылку проекта
				add_shortcode('awo_catalog_of_goods', array (&$this, 'get_catalog_of_goods')); // Вывод каталога товаров			
                add_shortcode('awo_goods_price', array (&$this, 'get_goods_price')); // Получение цены товара
			}
			
		}
		
		/**
		 * Подключаем вспомогательные виджеты
		 */
		public function include_widgets() 
		{
			// Вывод блока с произвольным текстом
			include_once( 'includes/widgets/AWO_Widget_Text.php' );
		}
		
		/**
		 * Функция включение Сессий
		 */
		function awo_session_start()
		{ 
			/*
			if (isset($_COOKIE['PHPSESSID'])) 
			{
				$sessid = $_COOKIE['PHPSESSID'];
			} 
			else if (isset($_GET['PHPSESSID'])) 
			{
				$sessid = $_GET['PHPSESSID'];
			} 
			else 
			{
				session_start();
				return false;
			}
        
			if (!preg_match('/^[a-z0-9]{32}$/', $sessid)) 
			{
				return false;
			}
			*/
			
			session_start();

			load_plugin_textdomain( 'autoweboffice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
			
			return true;
		}
		
		/**
		 * Загрузка необходимых скриптов для страницы управления 
		 * в панели администрирования
		 */
		function admin_load_scripts()
		{
		
		}
		
		/**
		 * Загрузка необходимых стилей для страницы управления 
		 * в панели администрирования
		 */
		function admin_load_styles()
		{	

		}
		
		/**
		 * Загрузка необходимых скриптов для страницы отображения 
		 * плагина на сайте
		 */
		function site_load_scripts()
		{	
			// $WP_ADMIN_URL = str_replace ('/wp-content', '/wp-admin', WP_CONTENT_URL);
			
			$WP_ADMIN_URL = admin_url();
			
			?>
			<script type='text/javascript'>
				var awo_wp_admin_url = "<?php echo $WP_ADMIN_URL;?>";
			</script>
			<?php
			// Регистрируем скрипты
			wp_register_script('awo_add_to_cart',$this->plugin_url.'js/awo_add_to_cart.js');
			
			// Добавляем скрипты на страницу
			wp_enqueue_script('awo_add_to_cart');
		}

		/**
		 * Загрузка необходимых стилей для страницы отображения 
		 * плагина на сайте
		 */
		function site_load_styles()
		{

		}
		
		/**
		 * Генерируем меню
		 */	
		function admin_generate_menu()
		{
			// Добавляем основной раздел меню
			add_menu_page('Интернет магазин', 'Магазин', 'edit_posts', 'awo-internet-shop', array(&$this, 'admin_internet_shop'), '../wp-content/plugins/autoweboffice-internet-shop/img/ico/ico.png');
			 
			// Добавляем дополнительный раздел
			add_submenu_page('awo-internet-shop', 'Настройки', 'Настройки', 'edit_posts', 'awo-options', array(&$this,'admin_options'));
			
			// Добавляем дополнительный раздел
			add_submenu_page('awo-internet-shop', 'Справка по работе с плагином Каталог товаров', 'Справка', 'edit_posts', 'awo-help', array(&$this,'admin_help'));
		
			// Добавляем дополнительный раздел
			add_submenu_page('awo-internet-shop', 'API', 'API', 'edit_posts', 'awo-api', array(&$this,'admin_options_api'));
		}
		
		/**
		 * Отображение страницы с указанием настроек плагина
		 */
		public function admin_options()
		{	
			global $wpdb;
			
			// Задаем значение полей по умолчанию
			$awo_goods_update_date = '0000-00-00 00:00:00'; // Дата последноне обновления товаров
			
			
			// Если передан action
			if(isset($_POST['action']))
			{
				// Получаем переданный action
				$action = $_POST['action'];
				
				switch ($action) 
				{
					case 'save_catalog_settings':
						// Сохраняем переданные настройки корзины заказа
						$result_update_catalog_settings = $this->admin_update_catalog_settings($_POST['awo_catalog_goods_width'], $_POST['awo_catalog_goods_per_page'], $_POST['awo_catalog_settings_submit_value']);
						
						break;
					case 'save_subscribe_form_settings':
						// Сохраняем переданные настройки отображения формы подписки на рассылку
						$result_update_api_settings = $this->admin_update_subscribe_form_settings($_POST['awo_id_newsletter'], $_POST['awo_id_advertising_channel_page'], $_POST['awo_last_name'], 
															  $_POST['awo_name'], 1, $_POST['awo_middle_name'],
															  $_POST['awo_phone_number'], $_POST['awo_policy_of_confidentiality'], $_POST['awo_subscribe_form_submit_value']);
						
						break; 
					case 'save_cart_settings':
						// Сохраняем переданные настройки корзины заказа
						$result_update_api_settings = $this->admin_update_cart_settings($_POST['awo_cart_settings_submit_value']);
						
						break;
                    case 'goods_update': 
						// Обновляем информацию по товарам
						$result_update_goods = $this->admin_update_goods();
						
						
						// Если данные по товарам успешно обновились, то сохраняем дату обновления в настройках плагина
						if($result_update_goods)
						{
							// Составляем массив для обновления данных
							$updateData = array(
										'goods_update_date' => date('Y-m-d H:i:s'),			
							);
							
							// Сохраняем дату последнего обносления товаров в настройках плагина
							$result_goods_update_date = $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
						}
						
						break; 
						
					case 'goods_truncate':
					
						$wpdb->query('TRUNCATE TABLE `'.$this->tbl_awo_goods.'`;');
						
						break; 
				}; 
			}
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем дату последнего обновления товаров
			$awo_goods_update_date = $this->get_carent_datetime(strtotime($awo_settings->goods_update_date));
			
			
			// Получаем массив с настройками формы подписки на расслку проекта
			$subscribe_form_settings = unserialize($awo_settings->subscribe_form_settings);

			$awo_id_newsletter = $subscribe_form_settings['id_newsletter']; 
			$awo_id_advertising_channel_page = $subscribe_form_settings['id_advertising_channel_page'];
			$awo_last_name = $subscribe_form_settings['last_name'];
			$awo_name = $subscribe_form_settings['name'];
			$awo_middle_name = $subscribe_form_settings['middle_name'];
			$awo_email = $subscribe_form_settings['email'];
			$awo_phone_number = $subscribe_form_settings['phone_number'];
			$awo_policy_of_confidentiality = $subscribe_form_settings['policy_of_confidentiality'];
			$awo_subscribe_form_submit_value = $subscribe_form_settings['subscribe_form_submit_value'];
			
			// Получаем массив с настройками отображения Корзины заказа
			$cart_settings = unserialize($awo_settings->cart_settings);
			
			$awo_cart_settings_submit_value = $cart_settings['cart_settings_submit_value']; 
			
			// Получаем массив с настройками отображения Каталога товаров
			$catalog_settings = unserialize($awo_settings->catalog_settings);
			
			$awo_catalog_goods_width = (int)$catalog_settings['awo_catalog_goods_width']; 
			
			if($awo_catalog_goods_width < '150')
			{
				$awo_catalog_goods_width = 150; 
			}
			
			$awo_catalog_goods_per_page = (int)$catalog_settings['catalog_goods_per_page']; 
			$awo_catalog_settings_submit_value = $catalog_settings['catalog_settings_submit_value']; 

			switch ($_GET['tab']) 
			{
				case 'subscribe_form':
					// Подключаем страницу с настроками Формы подписки
					include_once('admin/settings/subscribe_form.php');						
					break;
				case 'cart':
					// Подключаем страницу с настроками Корзины заказа
					include_once('admin/settings/cart.php');						
					break;
				default:
					// Подключаем страницу с настроками API
					include_once('admin/settings/catalog.php');						
					break;
			}
	
		}
		
		/**
		 * Отображение страницы с указанием настроек плагина
		 */
		public function admin_options_api()
		{	
			global $wpdb;
			
			// Задаем значение полей по умолчанию
			$awo_id_stores = ''; // Код магазина
			$awo_storesId = ''; // Уникальный идентификатор магазина
			$awo_api_key_get = ''; // Код получения данных по API
			$awo_goods_update_date = '0000-00-00 00:00:00'; // Дата последноне обновления товаров
			
			
			// Если передан action
			if(isset($_POST['action']))
			{
				// Получаем переданный action
				$action = $_POST['action'];
				
				switch ($action) 
				{
					case 'save_api_settings':
						// Сохраняем переданные настройки подключения по API
						$result_update_api_settings = $this->admin_update_api_settings($_POST['awo_id_stores'], $_POST['awo_storesId'], $_POST['awo_api_key_get']);
						
						break;
					case 'goods_update':
						// Обновляем информацию по товарам
						$result_update_goods = $this->admin_update_goods();
						
						
						// Если данные по товарам успешно обновились, то сохраняем дату обновления в настройках плагина
						if($result_update_goods)
						{
							// Составляем массив для обновления данных
							$updateData = array(
										'goods_update_date' => date('Y-m-d H:i:s'),			
							);
							
							// Сохраняем дату последнего обносления товаров в настройках плагина
							$result_goods_update_date = $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
						}
						
						break; 
						
					case 'goods_truncate':

						$wpdb->query('TRUNCATE TABLE `'.$this->tbl_awo_goods.'`;');
						
						break; 
				}; 
			}
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем дату последнего обновления товаров
			$awo_goods_update_date = $this->get_carent_datetime(strtotime($awo_settings->goods_update_date));
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_id_stores = $api_settings['id_stores']; 
			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
			

			switch ($_GET['tab']) 
			{
				default:
					// Подключаем страницу с настроками API
					include_once('admin/api/api.php');						
					break;
			}
	
		}
		
		/**
		 * Функция для отображения списка товаров в адм. панели
		 */
		public function admin_internet_shop()
		{
            global $wpdb;
			
			// Задаем значение полей по умолчанию
			$awo_goods_update_date = '0000-00-00 00:00:00'; // Дата последноне обновления товаров
			
			
			// Если передан action
			if(isset($_POST['action']))
			{
				// Получаем переданный action
				$action = $_POST['action'];
				
				switch ($action) 
				{
					case 'goods_update':
						// Обновляем информацию по товарам
						$result_update_goods = $this->admin_update_goods();
						
						// Обновляем информацию по категориям товаров
						$result_update_goods_category = $this->admin_update_goods_category();
						//var_dump($this->admin_update_goods_category()); exit();
						
						// Обновляем информацию по настройкам магазина
						// var_dump($this->admin_update_settings()); exit();
						$result_update_settings = $this->admin_update_settings();
						
						
						// Если данные по товарам успешно обновились, то сохраняем дату обновления в настройках плагина
						if($result_update_goods)
						{
							// Составляем массив для обновления данных
							$updateData = array(
										'goods_update_date' => date('Y-m-d H:i:s'),			
							);
							
							// Сохраняем дату последнего обносления товаров в настройках плагина
							$result_goods_update_date = $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
						}
						
						break; 
					
					case 'goods_truncate':

						$wpdb->query('TRUNCATE TABLE `'.$this->tbl_awo_goods.'`;');
						
						break; 
				}; 
			}
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_id_stores = $api_settings['id_stores']; 
			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
			
			// Получаем дату последнего обновления товаров
			$awo_goods_update_date = $this->get_carent_datetime(strtotime($awo_settings->goods_update_date));
			
			switch ($_GET['tab']) 
			{
				default:
					// Подключаем страницу с отображением списка товаров
					include_once('admin/internet-shop/goods.php');						
					break;
			}
				 
		}
		
		/**
		 * Функция получения данных по настройкам плагина
		 */
		private function admin_get_settings()
		{
			global $wpdb;
			
			// Получаем данные по настройкам плагина
			$awo_settings = $wpdb->get_row("SELECT * FROM `".$this->tbl_awo_settings."` WHERE `id_settings`= 1");
			
			return $awo_settings;
		}
		
		/**
		 * Функция для обновления информации о товарах
		 */
		private function admin_update_goods()
		{   
            // Обновляем категории
            $this->admin_update_goods_category();
            
            global $wpdb;
            
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
            
            
            
               
			
			// Если подключена библиотека cURL
			if($curl = curl_init())
			{
                
				// Массив с GET параметрами запроса
				$array_query = array(
								// API KEY
								'key' =>$awo_api_key_get,
				
								// Передаем критерии поиска
								// 'search[creation_date_start]' => '2014-06-01 00:00:00', // Дата создания счета От
								// 'search[creation_date_end]'=>'2014-07-01 00:00:00', // Дата создания счета До
								
								// Передаем настройки сортировки
								'param[sort]'=>'id_goods ASC', // Сортируем по возрастанию. Поле: Дата создания счета
								
								// Указываем дополнительные настройки выборки
								// 'param[pagesize]'=>'10', // Выводить по 10 элементов на стройке
								// 'param[currentpage]'=>'2', // Показать 2-ю строку
				);
				 
				$awo_storesId = trim($awo_storesId);
                
                
                $out_json = file_get_contents('https://'.$awo_storesId.'.autoweboffice.ru/?r=api/rest/goods&'.http_build_query($array_query));
                
                // Декодирует JSON строку в объект с данными
				$out_obj = json_decode($out_json);
                
				
				// Если не получили объект с данными, то выводим сообщение об ошибке
				if(!is_array($out_obj))
				{
					return false;
				}
                
				
				// Цикл по массиву с товарами
				foreach($out_obj as $key => $obj)
				{ 
                    // Проверяем существует ли данная категория 
                    $awo_goods_category = $wpdb->get_row("SELECT id_goods_category FROM `".$this->tbl_awo_goods_category."` WHERE id_goods_category = ".$obj->id_goods_category." LIMIT 1");
                    
					// Проверяем существует ли данный товар у наз в БД
					$awo_goods = $wpdb->get_row("SELECT id_goods FROM `".$this->tbl_awo_goods."` WHERE id_goods = ".$obj->id_goods." LIMIT 1");
					
					// Если товар уже существует, то обновляем информацию
					if($awo_goods)
					{
						
						$updateData['id_goods'] = $obj->id_goods;
						$updateData['marking'] = $obj->marking;
                        
                        if($awo_goods_category)
                        {
                            $updateData['id_goods_category'] = $obj->id_goods_category;
                        }
                        else 
                        {
                            $updateData['id_goods_category'] = 0;
                        }
                        
                        
						$updateData['in_affiliate'] = $obj->in_affiliate;
						$updateData['show_in_affiliate'] = $obj->show_in_affiliate;
						$updateData['goods'] = $obj->goods;
						$updateData['variants_name'] = $obj->variants_name;
						$updateData['image'] = $obj->image;
						$updateData['url_external_image'] = $obj->url_external_image;
						$updateData['url_external_image_used'] = $obj->url_external_image_used;
						$updateData['brief_description'] = $obj->brief_description;
						$updateData['price'] = $obj->price;
						$updateData['price_purchase'] = $obj->price_purchase;
						$updateData['url_page'] = $obj->url_page;
						$updateData['not_sold'] = $obj->not_sold;
						$updateData['not_sold_message'] = $obj->not_sold_message;
						$updateData['new_of_sales'] = $obj->new_of_sales;
						$updateData['hit_of_sales'] = $obj->hit_of_sales;
						$updateData['special_offer'] = $obj->special_offer;
						$updateData['id_goods_kind'] = $obj->id_goods_kind;
						$updateData['deleted'] = $obj->deleted;
						$updateData['creation_date'] = $obj->creation_date;
						$updateData['order_fields'] = $obj->order_fields;
						$updateData['information_single_order'] = $obj->information_single_order;
						$updateData['information_cart_order'] = $obj->information_cart_order;
						$updateData['additional_order_fields'] = $obj->additional_order_fields;
						$updateData['rest_in_stock'] = $obj->rest_in_stock;
						$updateData['id_supplier'] = $obj->id_supplier;
						$updateData['id_manufacturer'] = $obj->id_manufacturer;
						$updateData['id_employee_created'] = $obj->id_employee_created;
						$updateData['id_employee_deleted'] = $obj->id_employee_deleted;
						$updateData['deleted_date'] = $obj->deleted_date;
						$updateData['information_for_personal'] = $obj->information_for_personal;
						$updateData['show_license_agreement'] = $obj->show_license_agreement;
						$updateData['partner_program_levels_used'] = $obj->partner_program_levels_used;
						$updateData['partner_program_levels'] = $obj->partner_program_levels;
						$updateData['goods_color_name'] = $obj->goods_color_name;
						$updateData['goods_size_name'] = $obj->goods_size_name;
						$updateData['goods_color_used'] = $obj->goods_color_used;
						$updateData['goods_size_used'] = $obj->goods_size_used;
						
						// Чистим массив
						$formatData = array();
						
						// Составляем массив со значениями полей
						foreach ($updateData as $value)
						{
							$formatData[] = '%s'; // Для всех полей указываем формат - Строка
						}
                        
                    

						
						// Обновляем данные по Товару
						$wpdb->update($this->tbl_awo_goods, $updateData, array('id_goods' => $obj->id_goods), $formatData, array('%d'));
						
	
					}
					else // Если не существует, то добавляем данные по товару
					{
						
						$insertData['id_goods'] = $obj->id_goods;
						$insertData['marking'] = $obj->marking;
                        
                        if($awo_goods_category)
                        {
                            $insertData['id_goods_category'] = $obj->id_goods_category;
                        }
                        else 
                        {
                            $insertData['id_goods_category'] = 0;
                        }
                        
                        
						$insertData['in_affiliate'] = $obj->in_affiliate;
						$insertData['show_in_affiliate'] = $obj->show_in_affiliate;
						$insertData['goods'] = $obj->goods;
						$insertData['variants_name'] = $obj->variants_name;
						$insertData['image'] = $obj->image;
						$insertData['url_external_image'] = $obj->url_external_image;
						$insertData['url_external_image_used'] = $obj->url_external_image_used;
						$insertData['brief_description'] = $obj->brief_description;
						$insertData['price'] = $obj->price;
						$insertData['price_purchase'] = $obj->price_purchase;
						$insertData['url_page'] = $obj->url_page;
						$insertData['not_sold'] = (isset($obj->not_sold) ? $obj->not_sold : '');
						$insertData['not_sold_message'] = $obj->not_sold_message;
						$insertData['new_of_sales'] = $obj->new_of_sales;
						$insertData['hit_of_sales'] = $obj->hit_of_sales;
						$insertData['special_offer'] = $obj->special_offer;
						$insertData['id_goods_kind'] = $obj->id_goods_kind;
						$insertData['deleted'] = (isset($obj->deleted) ? $obj->deleted : '');
						$insertData['creation_date'] = $obj->creation_date;
						$insertData['order_fields'] = (isset($obj->order_fields) ? $obj->order_fields : '');
						$insertData['information_single_order'] = (isset($obj->information_single_order) ? $obj->information_single_order : '');
						$insertData['information_cart_order'] = (isset($obj->information_cart_order) ? $obj->information_cart_order : '');
						$insertData['additional_order_fields'] = (isset($obj->additional_order_fields) ? $obj->additional_order_fields : '');
						$insertData['rest_in_stock'] = $obj->rest_in_stock;
						$insertData['id_supplier'] = $obj->id_supplier;
						$insertData['id_manufacturer'] = $obj->id_manufacturer;
						$insertData['id_employee_created'] = (isset($obj->id_employee_created) ? $obj->id_employee_created : '');
						$insertData['id_employee_deleted'] = (isset($obj->id_employee_deleted) ? $obj->id_employee_deleted : '');
						$insertData['deleted_date'] = (isset($obj->deleted_date) ? $obj->deleted_date : '');
						$insertData['information_for_personal'] = (isset($obj->information_for_personal) ? $obj->information_for_personal : '');
						$insertData['show_license_agreement'] = (isset($obj->show_license_agreement) ? $obj->show_license_agreement : '');
						$insertData['partner_program_levels_used'] = (isset($obj->partner_program_levels_used) ? $obj->partner_program_levels_used : '');
						$insertData['partner_program_levels'] = (isset($obj->partner_program_levels) ? $obj->partner_program_levels : '');
						$insertData['goods_color_name'] = $obj->goods_color_name;
						$insertData['goods_size_name'] = $obj->goods_size_name;
						$insertData['goods_color_used'] = $obj->goods_color_used;
						$insertData['goods_size_used'] = $obj->goods_size_used;
						
                        
                        
                        
						// Чистим массив
						$formatData = array();
						
						// Составляем массив со значениями полей
						foreach ($insertData as $value)
						{
							$formatData[] = '%s'; // Для всех полей указываем формат - Строка
						}
						                        
						$wpdb->insert($this->tbl_awo_goods, $insertData, $formatData);
                        
                        //echo $wpdb->last_error."-error<br>";
                        
                        
					}
				}
			}
			else
			{
				return false;
			}

			return true;
		}
		
		
		/**
		 * Функция для обновления информации о категориях товаров
		 */
		private function admin_update_goods_category()
		{	
			global $wpdb;
            
            // Очищаем от старой информации
            $wpdb->query('TRUNCATE TABLE '.$this->tbl_awo_goods_category);
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
			
			// Если подключена библиотека cURL
			if($curl = curl_init())
			{
				// Массив с GET параметрами запроса
				$array_query = array(
								// API KEY
								'key' =>$awo_api_key_get,
				
								// Передаем критерии поиска
								// 'search[creation_date_start]' => '2014-06-01 00:00:00', // Дата создания счета От
								// 'search[creation_date_end]'=>'2014-07-01 00:00:00', // Дата создания счета До
								
								// Передаем настройки сортировки
								'param[sort]'=>'id_goods_category ASC', // Сортируем по возрастанию. Поле: Дата создания счета
								
								// Указываем дополнительные настройки выборки
								// 'param[pagesize]'=>'10', // Выводить по 10 элементов на стройке
								// 'param[currentpage]'=>'2', // Показать 2-ю строку
				);
				 
				$awo_storesId = trim($awo_storesId);

				curl_setopt($curl, CURLOPT_URL, 'https://'.$awo_storesId.'.autoweboffice.ru/?r=api/rest/goodscategory&'.http_build_query($array_query));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				
				$out_json = curl_exec($curl);
				
				curl_close($curl);
				
				
				// Декодирует JSON строку в объект с данными
				$out_obj = json_decode($out_json);
				
				// Если не получили объект с данными, то выводим сообщение об ошибке
				if(!is_array($out_obj))
				{
					return false;
				}
				

				// Цикл по массиву с категориями товаров
				foreach($out_obj as $key => $obj)
				{
				
					// Проверяем существует ли данный категория товара у нас в БД
					$awo_goods_category = $wpdb->get_row("SELECT id_goods_category FROM `".$this->tbl_awo_goods_category."` WHERE id_goods_category = ".$obj->id_goods_category);
					
					// Если категория уже существует, то обновляем информацию
					if($awo_goods_category)
					{
						
						$updateData['id_goods_category'] = $obj->id_goods_category;
						$updateData['goods_category'] = $obj->goods_category;
						$updateData['brief_description'] = $obj->brief_description;
						$updateData['id_goods_category_parent'] = $obj->id_goods_category_parent;
						$updateData['id_employee_created'] = $obj->id_employee_created;
						$updateData['id_employee_deleted'] = $obj->id_employee_deleted;
						$updateData['deleted'] = $obj->deleted;
						$updateData['deleted_date'] = $obj->deleted_date;
						$updateData['creation_date'] = $obj->creation_date;
						
						// Чистим массив
						$formatData = array();
						
						// Составляем массив со значениями полей
						foreach ($updateData as $value)
						{
							$formatData[] = '%s'; // Для всех полей указываем формат - Строка
						}

						
						// Обновляем данные по Товару
						$wpdb->update($this->tbl_awo_goods_category, $updateData, array('id_goods_category' => $obj->id_goods_category), $formatData, array('%d'));
						
	
					}
					else // Если не существует, то добавляем данные по категории товара
					{

						$insertData['id_goods_category'] = $obj->id_goods_category;
						$insertData['goods_category'] = (isset($obj->goods_category) ? $obj->goods_category : '');
						$insertData['brief_description'] = (isset($obj->brief_description) ? $obj->brief_description : '');
						$insertData['id_goods_category_parent'] = (isset($obj->id_goods_category_parent) ? $obj->id_goods_category_parent : '');
						$insertData['id_employee_created'] = (isset($obj->id_employee_created) ? $obj->id_employee_created : '');
						$insertData['id_employee_deleted'] = (isset($obj->id_employee_deleted) ? $obj->id_employee_deleted : '');
						$insertData['deleted'] = (isset($obj->deleted) ? $obj->deleted : '');
						$insertData['deleted_date'] = (isset($obj->deleted_date) ? $obj->deleted_date : '');
						$insertData['creation_date'] = (isset($obj->creation_date) ? $obj->creation_date : '');
						
						// Чистим массив
						$formatData = array();
						
						// Составляем массив со значениями полей
						foreach ($insertData as $value)
						{
							$formatData[] = '%s'; // Для всех полей указываем формат - Строка
						}
						
						$wpdb->insert($this->tbl_awo_goods_category, $insertData, $formatData);
					}
				}
			}
			else
			{
				return false;
			}
			
			return true;
		}
		
		
		/**
		 * Функция для обновления информации о настройках магазина
		 */
		private function admin_update_settings()
		{	
			global $wpdb;
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
			$id_stores = $api_settings['id_stores'];
			
			// Если подключена библиотека cURL
			if($curl = curl_init())
			{
				// Массив с GET параметрами запроса
				$array_query = array(
								// API KEY
								'key' =>$awo_api_key_get,
								'id' =>$id_stores,	
				);
				 
				$awo_storesId = trim($awo_storesId);

				curl_setopt($curl, CURLOPT_URL, 'https://'.$awo_storesId.'.autoweboffice.ru/?r=api/rest/mainsettings&'.http_build_query($array_query));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				
				$out_json = curl_exec($curl);
				
				curl_close($curl);
				
				// Декодирует JSON строку в объект с данными
				$out_obj = json_decode($out_json);

				// Если не получили объект с данными, то выводим сообщение об ошибке
				if(!is_object($out_obj))
				{
					return false;
				}

				// Составляем массив для обновления данных
				$updateData = array(
							'id_currency' => $out_obj->id_currency,			
				);
							
				
				// Сохраняем в настройках плагина
				$wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
							
			}
			else
			{
				return false;
			}
			
			return true;
		}
		
		
		
		/**
		 * Функция сохранения настроек подключения по API
		 * $id_stores - код магазина
		 * $storesId - идентификатор магазина
		 * $api_key_get - ключ получения данных по API
		 */
		private function admin_update_api_settings($id_stores, $storesId, $api_key_get)
		{	
			global $wpdb;
			
			$id_stores = trim($id_stores);
			$storesId = trim($storesId);
			$api_key_get = trim($api_key_get);
			
			// Составляем сюриализованный массив со значениями настроек подключения
			$api_settings = serialize(array('id_stores'=>$id_stores,
											'storesId'=>$storesId, 
											'api_key_get' => $api_key_get));
			
			// Составляем массив для обновления данных
			$updateData = array(
						'api_settings' => $api_settings,			
			);
			
			
			// Обновляем данные по настройкам подключения к API
			return $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
		}	
		
		/**
		 * Функция сохранения настроек каталога магазина
		 * $awo_catalog_goods_width - Ширина блока с одним товаром
		 * $catalog_goods_per_page - Количество товаров на странице
		 * $catalog_settings_submit_value - Надпись на кнопке Добавить в корзину
		 */
		private function admin_update_catalog_settings($awo_catalog_goods_width, $catalog_goods_per_page, $catalog_settings_submit_value)
		{	
			global $wpdb;
			
			// Составляем сюриализованный массив со значениями настроек подключения
			$catalog_settings = serialize(array('awo_catalog_goods_width'=>$awo_catalog_goods_width, 
											'catalog_goods_per_page'=>$catalog_goods_per_page, 
											'catalog_settings_submit_value' => $catalog_settings_submit_value));
			
			// Составляем массив для обновления данных
			$updateData = array(
						'catalog_settings' => $catalog_settings,			
			);
			
			// Обновляем данные по настройкам подключения к API
			return $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
		}	
		
		/**
		 * Функция сохранения настроек формы подписки на рассылку проекта
		 * $id_newsletter - Код рассылки
		 * $id_advertising_channel_page - Код канала рекламы
		 * $last_name - Запрашивать или нет Фамилию
		 * $name - Запрашивать или нет Имя
		 * $middle_name - Запрашивать или нет Отчество
		 * $email - Запрашивать или нет Email
		 * $phone_number - Запрашивать или нет Телефон
		 * $policy_of_confidentiality - Политика конфеденциальности
		 * $subscribe_form_submit_value - Надпись на кнопке
		 */
		private function admin_update_subscribe_form_settings($id_newsletter, $id_advertising_channel_page, $last_name, 
															  $name, $email, $middle_name,
															  $phone_number, $policy_of_confidentiality, $subscribe_form_submit_value)
		{	
			global $wpdb;
			
			// Составляем сюриализованный массив со значениями настроек подключения
			$subscribe_form_settings = serialize(array('id_newsletter'=>$id_newsletter,
											'id_advertising_channel_page'=>$id_advertising_channel_page, 
											'last_name' => $last_name, 
											'name' => $name, 
											'middle_name' => $middle_name, 
											'email' => $email, 
											'phone_number' => $phone_number, 
											'policy_of_confidentiality' => $policy_of_confidentiality, 
											'subscribe_form_submit_value' => $subscribe_form_submit_value));
			
			// Составляем массив для обновления данных
			$updateData = array(
						'subscribe_form_settings' => $subscribe_form_settings,			
			);
			
			// Обновляем данные по настройкам подключения к API
			return $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
		}
		
		/**
		 * Функция сохранения настроек Корзины заказа
		 * $awo_cart_settings_submit_value - Надпись на кнопке Оформить заказ
		 */
		private function admin_update_cart_settings($cart_settings_submit_value)
		{	
			global $wpdb;
			
			// Составляем сюриализованный массив со значениями настроек подключения
			$cart_settings = serialize(array('cart_settings_submit_value'=>$cart_settings_submit_value));
			
			// Составляем массив для обновления данных
			$updateData = array(
						'cart_settings' => $cart_settings,			
			);
			
			// Обновляем данные по настройкам подключения к API
			return $wpdb->update($this->tbl_awo_settings, $updateData, array('id_settings' => 1));
		}
		
		/**
		 * Показываем статическую страницу с информацией о плагине
		 */
		public function admin_help()
		{
			switch ($_GET['tab']) 
			{
				case 'subscribe_form':
					// Публикация формы подписки на рассылку
					include_once('admin/help/subscribe_form.php');						
					break;
				case 'order':
					// Публикация каьалога и кнопок на заказ
					include_once('admin/help/order.php');						
					break;
				case 'settings':
					// Работа с настройками
					include_once('admin/help/settings.php');						
					break;
				default:
					// Работа с API
					include_once('admin/help/api.php');						
					break;
			}
		}
		
		/**
		 * Функция добавления товара в Корзину заказа
		 */
		function ajax_add_goods_to_order() 
		{
			// Если передан код твоара
			if(isset($_REQUEST['id_goods']) AND $_REQUEST['id_goods'] > 0)
			{
				// Получаем ранее накопленные данные по товарам
				$Goods = $_SESSION['awo_shopping_cart'];
				
				// Обновляем количество товара данного наименования в Корзине заказа
				$Goods[$_REQUEST['id_goods']]['quantity'] = $Goods[$_REQUEST['id_goods']]['quantity'] + 1;
				
				// Помещаем массив в сессию
				$_SESSION['awo_shopping_cart'] = $Goods; // array_merge($_SESSION['awo_shopping_cart'], $Goods);
			}

			// Получаем данные
			$result = $this->get_cart_info_shot();
			
			echo $result;
			
			// !Важно не забыть убить функцию.
			die();
		}
		
		/**
		 * Функция удаления товара из Корзины заказа
		 */
		function ajax_delete_from_cart()
		{
			// Получаем ранее накопленные данные по товарам
			$Goods = $_SESSION['awo_shopping_cart'];
			
			// Обновляем количество товара данного наименования в Корзине заказа
			unset($Goods[$_REQUEST['id_goods']]['quantity']);
			
			// Помещаем массив в сессию
			$_SESSION['awo_shopping_cart'] = $Goods; // array_merge($_SESSION['awo_shopping_cart'], $Goods);

			// Получаем данные
			$result = $this->ajax_show_message();
			
			echo $result;
			
			// !Важно не забыть убить функцию.
			die();
		}
		
		
		/**
		 * Функция отображения Корзины заказа
		 */
		function ajax_show_message()
		{
			$cart_quantity = 0;
			$cart_sum = 0;
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
								
			// Если не указан Идентификатор магазина
			if(trim($awo_storesId) == '')
			{
				return 'Не указан Идентификатор магазина!';
			}
			
			include_once('html/html_cart.php');

					
			echo $cart_info_shot;
			
			// !Важно не забыть убить функцию.
			die();
		}
		
		/**
		 * Функция передачи данных о заказе в АвтоОфис
		 */
		function ajax_empty_cart()
		{
			// Если существует массив с данными по корзине заказа
			if(isset($_SESSION['awo_shopping_cart']))
			{			
				unset($_SESSION['awo_shopping_cart']);
			}
		}
		
		/**
		 * Функция получения данных по товару
		 * $id_goods - Код товара
		 */
		public function get_goods($id_goods)
		{
			global $wpdb;
			
			// Получаем данные по настройкам плагина
			$awo_goods = $wpdb->get_row("SELECT * FROM `".$this->tbl_awo_goods."` WHERE `id_goods`= '".$id_goods."'");
			
			return $awo_goods;
		}
		
		/*
		 * Получаем текущее значение даты с учетом настроек часового пояса
		 * $time - текущая метка времени
		 * $formate - фотрмат отображения даты и времени
		 */
		public function get_carent_datetime($time, $formate = '')
		{
			// Если не передан формат, то берем формат из настроек по умолчанию
			if($formate == '')
			{
				$formate = get_option('date_format').' '.get_option('time_format');
			}
			
			return date($formate , $time + get_option('gmt_offset') * HOUR_IN_SECONDS);
		}
		
		/**
		 * Функция преобразования Шоткода в кнопку Добавить в корзину
		 */
		public function get_link_to_order($atts)
		{
			// Отвечает за запросы к базе данных
			global $wpdb;
			
			// Получаем значения переданных атрибутов
			extract(shortcode_atts(array(
				'id_goods' => '0', // Если атрибут id_goods не указан, то по умолчанию ставим 0
				'add_to_cart_submit_value' => 'Добавить в корзину',
				'style' => ''
			), $atts, 'awo_link_to_order'));
			
			// Получаем данные по указанному товару
			$awo_goods = $wpdb->get_row("SELECT id_goods FROM `".$this->tbl_awo_goods."` WHERE id_goods = ".$id_goods);
					
			// Если передан код не существующего товара
			if(!$awo_goods)
			{
				return '';
			}
			
			if($awo_goods->not_sold != 0)
			{
				return 'Товар снят с продажи...';
			}
			
			// Для вывода системных сообщений
			$link_to_order .= '<div class="awo_show_message" style="border-radius: 5px;
								border: 1px double black;
								position: fixed;
								z-index: 1000;
								max-width: 90%;
								max-height: 70%;
								min-width: 90%;
								min-height: 70%;
								overflow: auto;
								top: 15%;
								left: 5%;
								padding: 20px 20px 20px 20px;
								background-color: white;
								display:none;"></div>';
					
			$link_to_order .= '<input type="submit" ';
			
			if(trim($style) != '')
			{
				$link_to_order .= ' style="'.$style.'" ';
			}
			
			$link_to_order .= ' class="awo_add_to_cart" id="'.$id_goods.'" value="'.$add_to_cart_submit_value.'">';
			
			
			return $link_to_order;
		}
		
        
        
        /**
		 * Функция преобразования Шоткода в цену товара
		 */
		public function get_goods_price($atts)
		{
			// Отвечает за запросы к базе данных
			global $wpdb;
			
			// Получаем значения переданных атрибутов
			extract(shortcode_atts(array(
				'id_goods' => '0', // Если атрибут id_goods не указан, то по умолчанию ставим 0
			), $atts, 'awo_goods_price'));
			
			// Получаем данные по указанному товару
			$awo_goods = $wpdb->get_row("SELECT price FROM `".$this->tbl_awo_goods."` WHERE id_goods = ".$id_goods);
					
			// Если передан код не существующего товара
			if(!$awo_goods)
			{
				return '';
			}
			
			if($awo_goods->not_sold != 0)
			{
				return 'Товар снят с продажи...';
			}
			
            
			// Для вывода системных сообщений
			$price = $awo_goods->price;
			
			return $price;
		}
        
        
        
        
		/**
		 * Функция преобразования Шоткода в Каталог товаров
		 */
		public function get_catalog_of_goods($atts)
		{	
			// Отвечает за запросы к базе данных
			global $wpdb;
			
			// Получаем значения переданных атрибутов
			extract(shortcode_atts(array( 
				'awo_catalog_goods_width' => '150',
				'catalog_goods_per_page' => '',
				'catalog_show_search' => '1',
				'catalog_goods_type' => 'default',
                'catalog_show_category' => '0',
				'catalog_settings_submit_value' => '',
                'catalog_show_all_goods' => '0',
			), $atts, 'awo_catalog_of_goods'));
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
			
			$id_goods = (int)$_GET['id_goods'];
			
			if(isset($_GET['id_goods_category'])) $id_goods_category = (int)$_GET['id_goods_category'];
			
			
			// Если передан код товара
			if($id_goods > 0)
			{
				// Получаем данные по товару из БД
				$awo_goods = $wpdb->get_row("SELECT * FROM `".$this->tbl_awo_goods."` WHERE deleted=0 AND id_goods= ".$id_goods);
			
				include_once('html/html_goods.php');
				exit();
			}
			
			
			// Получаем массив с настройками отображения Каталога товаров
			$catalog_settings = unserialize($awo_settings->catalog_settings);
			
			// Если передали настройки через параметры Шоткода
			if($awo_catalog_goods_width != 0 AND $awo_catalog_goods_width > 150)
			{
				$awo_catalog_goods_width = (int)$awo_catalog_goods_width;
			}
			else
			{
				$awo_catalog_goods_width = (int)$catalog_settings['awo_catalog_goods_width']; 
			}
			
			// Если передали настройки через параметры Шоткода
			if($catalog_goods_per_page != '')
			{
				$awo_catalog_goods_per_page = (int)$catalog_goods_per_page;
			}
			else
			{
				$awo_catalog_goods_per_page = (int)$catalog_settings['catalog_goods_per_page']; 
			}
			
			// Если передали настройки через параметры Шоткода
			if($catalog_settings_submit_value != '')
			{
				$awo_catalog_settings_submit_value = $catalog_settings_submit_value;
			}
			else
			{
				$awo_catalog_settings_submit_value = $catalog_settings['catalog_settings_submit_value']; 
			}
			
			// Надпись на кнопке не должна быть пустой
			if(trim(awo_catalog_settings_submit_value) == '')
			{
				$awo_catalog_settings_submit_value = 'Добавить в корзину';
			}
				
				
			// Категории и товары к ним (Главная страница)
			$awo_goods_category_sql = "	SELECT * 
								FROM `".$this->tbl_awo_goods_category."` 
								WHERE deleted=0 
								AND id_goods_category_parent = 0
								ORDER BY goods_category
								";

			// Получаем данные по Категориям товара
			$awo_goods_category = $wpdb->get_results($awo_goods_category_sql);
				
			// Если нет категорий
			if(count($awo_goods_category) == 0)
			{
				$id_goods_category = 0;
			}
			
			// Для составления условий поиска
			$where = '';
			
			$awo_search_goods = trim($_POST['awo_search_goods']);
			
			// Если переданы критерии поиска по товарам
			if($awo_search_goods != '' AND $awo_search_goods != 'Поиск по всем товарам')
			{
				$where .= " AND `goods` LIKE '%".$awo_search_goods."%'";
			}
			
			// Если указан тип каталога
			if($catalog_goods_type != 'default')
			{
				switch ($catalog_goods_type) 
				{
					case 'new': // Новинки
						$where .= " AND `new_of_sales` != 0";
						break; 
						
					case 'hit': // Хит продаж
						$where .= " AND `hit_of_sales` != 0";
						break;
						
					case 'special': // Хит продаж
						$where .= " AND `special_offer` != 0";
						break;
				}
			}
			
			// Если указана категория товара
			if($id_goods_category>0 OR ( isset($_GET['id_goods_category']) AND $_GET['id_goods_category'] == 0 ) 
                OR ( isset($catalog_show_all_goods) AND $catalog_show_all_goods > 0 )
                )
			{
				
				// Вычисляем номер первого товара
				$paged = 0;
                
                if(get_query_var('page')  >  1)
                {
                    $paged = get_query_var('page');
                }
                elseif (get_query_var('paged')  >  1) 
                {
                    $paged = get_query_var('paged');
                }
				
				$limit_start = 0;
				
				// Если номер страницы больше 0 и нет критерий поиска
				if($paged > 0 AND $_POST['awo_search'] != true)
				{
					$limit_start = ($paged - 1) * $awo_catalog_goods_per_page;
				}
				
				// Получаем данные по Категории товара
				$awo_goods_category = $wpdb->get_results("SELECT * 
														FROM `".$this->tbl_awo_goods_category."` 
														WHERE deleted=0 
														AND id_goods_category=".$id_goods_category."
														LIMIT 1
														");
				
				
				$awo_goods_sql = "	SELECT * 
									FROM `".$this->tbl_awo_goods."` 
									WHERE deleted=0 
										AND not_sold=0 ";
				(isset($id_goods_category)) ? $awo_goods_sql .= "AND id_goods_category=".$id_goods_category : "";
				$awo_goods_sql .=		" AND awo_not_show=0 ".$where." 
									ORDER BY  `goods` ASC  
									LIMIT ".$limit_start." , ".$awo_catalog_goods_per_page."";

               
                
				// Получаем данные по товарам
				$awo_goods = $wpdb->get_results($awo_goods_sql);	

				// Получаем данные по количеству товаров
                $sql_goods_count .=     "SELECT COUNT(*) AS goods_count 
                                        FROM `".$this->tbl_awo_goods."` 
                                        WHERE deleted=0 
                                            AND not_sold=0 ";
                (isset($id_goods_category)) ? $sql_goods_count .= " AND id_goods_category=".$id_goods_category : "";
				$sql_goods_count .=					" AND awo_not_show=0 ".$where;
                    
                $awo_goods_count = $wpdb->get_results($sql_goods_count);
                                                        
															
				$goods_count = $awo_goods_count['0']->goods_count;
                
                
				
				$html_catalog = '';
				
				// Для вывода системных сообщений
				$html_catalog .= '<div class="awo_show_message" style="border-radius: 5px;
									border: 1px double black;
									position: fixed;
									z-index: 1000;
									max-width: 1000px;
									max-height: 70%;
									min-width: 800px;
									min-height: 70%;
									overflow: auto;
									top: 15%;
									margin: 0 auto;
									padding: 20px 20px 20px 20px;
									background-color: white;
									display:none;"></div>';
				
				// Вычисляем максимальное количество страниц
				$total_pages = ceil($goods_count/$awo_catalog_goods_per_page);
				
				// Выводить поле поиска по товарам
				if($catalog_show_search != 0)
				{
					include_once('html/html_catalog_search.php');
				}
				

				// Подключаем страницу с настроками Формы подписки
				include_once('html/html_catalog_category.php');					

				
				// Получаем код пагинации для страницы
				$paginate_links = $this->get_paginate_links($total_pages);
				
				$html_catalog .= '<div>'.$paginate_links.'</div>';
				
				
				return $html_catalog;
				
			}
			else
			{
							
				$html_catalog = '';
				
				// Для вывода системных сообщений
				$html_catalog .= '<div class="awo_show_message" style="border-radius: 5px;
									border: 1px double black;
									position: fixed;
									z-index: 1000;
									max-width: 1000px;
									max-height: 70%;
									min-width: 800px;
									min-height: 70%;
									overflow: auto;
									top: 15%;
									left: 5%;
									padding: 20px 20px 20px 20px;
									background-color: white;
									display:none;"></div>';
					
				// Выводить поле поиска по товарам
				if($catalog_show_search != 0)
				{
					include_once('html/html_catalog_search.php');
				}

                if($catalog_show_category == 1)
                {
                    include_once('html/html_catalog_without_category.php');
                }
                else
                { 
                    // Если поиск, то отображать каталог не надо
                    if( $_POST['awo_search'] == true AND $awo_search_goods != '' AND $awo_search_goods != 'Поиск по всем товарам')
                    {
                        include_once('html/html_catalog_without_category.php');
                    }
                    else
                    {
                        include_once('html/html_catalog.php');	
                    }
                }
				
							

				return $html_catalog;
			}
			
			
		}
		
		/**
		 * Функция отображения пагинации на страницах
		 */
		public function get_paginate_links($total_pages)
		{
			/*
			$big = 999999999; // уникальное число для замены

			$args = array(
				'base' 			=> str_replace($big, '%#%', get_pagenum_link($big)),
				'format' 		=> '',
				'current' 		=> max(1, $_GET['paged']),
				'show_all'     	=> false,
				'end_size'     	=> 3,
				'mid_size'     	=> 3,
				'total' 		=> $total_pages,
				'prev_next'   	=> true,
				'prev_text'    	=> '«««',
				'next_text'		=> '»»»'
			);

			$paginate_links = paginate_links( $args );

			// удаляем добавку к пагинации для первой страницы
			return str_replace( '/paged/1', '', $paginate_links );
			*/
			
			global $wp_query ;
			
			$big =  999999999 ;
			
            
			if($_GET['page'] >  1)
			{
				$current = $_GET['page'];
			}
            elseif ($_GET['paged']  >  1) 
            {
                $current = $_GET['paged'];
            }
			else
			{
				$current = 1; 
			}

			return paginate_links ( array ( 
				'base'  =>  @add_query_arg ( 'paged' , '%#%' ), 
				'format'  =>  '&paged=%#%' , 
				'current'  => $current, 
				'show_all'     	=> false,
				'end_size'     	=> 3,
				'mid_size'     	=> 3,
				'total'  => $total_pages , 
				'prev_next'   	=> true,
				'prev_text'    	=> '«««',
				'next_text'		=> '»»»'
			)  ); 
		}
		
		/**
		 * Функция преобразования Шоткода в ссылку Заказать прямо сейчас
		 */
		public function get_link_to_single_order($atts)
		{
			// Сохраняем UTM-метоки, если они переданы в запросе
			$this->save_utm();
		
			// Отвечает за запросы к базе данных
			global $wpdb;
			
			// Получаем значения переданных атрибутов
			extract(shortcode_atts(array(
				'id_goods' => '0', // Если атрибут id_goods не указан, то по умолчанию ставим 0
				'add_to_cart_submit_value' => 'Заказать прямо сейчас',
				'quantity' => '1',
				'style' => ''
			), $atts, 'awo_link_to_single_order'));
			
			// Получаем данные по указанному товару
			$awo_goods = $wpdb->get_row("SELECT id_goods FROM `".$this->tbl_awo_goods."` WHERE id_goods = ".$id_goods);
					
			// Если передан код не существующего товара
			if(!$awo_goods)
			{
				return '';
			}
			
			if($awo_goods->not_sold != 0)
			{
				return 'Товар снят с продажи...';
			}
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
			
			// Если не указан Идентификатор магазина
			if(trim($awo_storesId) == '')
			{
				return 'Не указан Идентификатор магазина!';
			}
			
			$link_to_order = '<input type="submit" ';
			
			if(trim($style) != '')
			{
				$link_to_order .= ' style="'.$style.'" ';
			}
						
			// Если в сессии хранятся данные по UTM-меткам
			if(isset($_SESSION['awo_utm']))
			{
				// Получаем данные по UTM-меткам
				$utm = $_SESSION['awo_utm'];
				
				$link_to_order .= ' value="'.$add_to_cart_submit_value.'" 
					onClick="location.href=\'http://'.$awo_storesId.'.autoweboffice.ru/?r=ordering/cart/as1&id='.$id_goods.'&clean=true&quantity='.$quantity.'&lg=ru&utm_source='.$utm['utm_source']
																																								.'&utm_campaign='.$utm['utm_campaign']
																																								.'&utm_term='.$utm['utm_term']
																																								.'&utm_content='.$utm['utm_content']
																																								.'&utm_medium='.$utm['utm_medium'].'\'">';
	
			}
			else
			{
				$link_to_order .= ' value="'.$add_to_cart_submit_value.'" 
					onClick="location.href=\'http://'.$awo_storesId.'.autoweboffice.ru/?r=ordering/cart/as1&id='.$id_goods.'&clean=true&quantity='.$quantity.'&lg=ru\'">';
			}
			
			return $link_to_order;
		}
		
		/**
		 * Функция вывода краткой информации по заказу
		 */
		public function get_cart_info_shot()
		{
			// Сохраняем UTM-метоки, если они переданы в запросе
			$this->save_utm();
		
			$cart_quantity = 0;
			$cart_sum = 0;
			
			// Если существует массив с данными по корзине заказа
			if(isset($_SESSION['awo_shopping_cart']))
			{			
				// Для передачи данных по товарам в АвтоОфис
				$html_form_goods = '';
				
				foreach ($_SESSION['awo_shopping_cart'] AS $key => $goods)
				{
					// Количество товара по умолчанию
					$quantity = 1;
					
					// Получаем код товара
					$id_goods = (int)$key;
					
					// Получаем колчество товара
					$quantity = (int)$goods['quantity'];
					
					// Высчитываем общее количество товаров
					$cart_quantity += $quantity;
					
					// Получаем данные по товару
					$awo_goods = $this->get_goods($id_goods);
					
					$cart_sum += $awo_goods->price * $quantity;
					
					$html_form_goods .= '<input type="hidden" value="'.$quantity.'" name="Goods['.$id_goods.'][quantity]">';
				}
			}
			
			$cart_info_shot = '';
			
			$cart_info_shot .= '<div class="awo_cart" style="">';
			$cart_info_shot .= '<p>Товаров: ';
			
			$cart_info_shot .= $cart_quantity.' шт.';
			
			$cart_info_shot .= '<br />На сумму: '.number_format($cart_sum, 2, '.', ' ').' '.$this->get_currency_str().'</p>';
								
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_storesId = $api_settings['storesId'];
								
			// Если не указан Идентификатор магазина
			if(trim($awo_storesId) == '')
			{
				return 'Не указан Идентификатор магазина!';
			}
			
			// Если в корзине есть товары
			if($cart_quantity > 0)
			{	
				
				// Если в сессии хранятся данные по UTM-меткам
				if(isset($_SESSION['awo_utm']))
				{
					// Получаем данные по UTM-меткам
					$utm = $_SESSION['awo_utm'];
				
					// Составляем форму для отправки запроса в АвтоОфис
					$cart_info_shot .= '<form class="awo_checkout" action="https://'.$awo_storesId.'.autoweboffice.ru/?r=ordering/cart/s1&clean=true&utm_source='.$utm['utm_source']
																																			   .'&utm_campaign='.$utm['utm_campaign']
																																			   .'&utm_term='.$utm['utm_term']
																																			   .'&utm_content='.$utm['utm_content']
																																			   .'&utm_medium='.$utm['utm_medium'].'" method="post" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8">';
				}
				else
				{
					// Составляем форму для отправки запроса в АвтоОфис
					$cart_info_shot .= '<form class="awo_checkout" action="https://'.$awo_storesId.'.autoweboffice.ru/?r=ordering/cart/s1&clean=true" method="post" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8">';

				}
				
				// Добавляем данные по товарам
				$cart_info_shot .= $html_form_goods;
				
				// Получаем массив с настройками отображения Корзины заказа
				$cart_settings = unserialize($awo_settings->cart_settings);
				
				$awo_cart_settings_submit_value = $cart_settings['cart_settings_submit_value']; 
				
				// Надпись на кнопке не должна быть пустой
				if(trim($awo_cart_settings_submit_value) == '')
				{
					$awo_cart_settings_submit_value = 'Оформить заказ';
				}
				
				$cart_info_shot .= '<input type="submit" style="margin-top: 10px;" value="'.$awo_cart_settings_submit_value.'" >';
				
				$cart_info_shot .= '</form>';
			}
								
			$cart_info_shot .= '</div>';
			
			return $cart_info_shot;
		}
		
		/**
		 * Вывод формы подписки на рассылку проекта
		 */
		public function get_subscribe_form($atts)
		{
			// Получаем значения переданных атрибутов
			extract(shortcode_atts(array(
				'id_newsletter' => '', // Если атрибут id_newsletter не указан, то по умолчанию ставим 0
				'id_advertising_channel_page' => '',
				'last_name' => '',
				'name' => '',
				'middle_name' => '',
				'email' => '',
				'phone_number' => '',
				'policy_of_confidentiality' => '',
				'subscribe_form_submit_value' => '',
			), $atts, 'awo_subscribe_form'));
			
			// Получаем данные по настройкам плагина
			$awo_settings = $this->admin_get_settings();
			
			// Получаем массив с настройками подключения по API
			$api_settings = unserialize($awo_settings->api_settings);

			$awo_id_stores = $api_settings['id_stores']; 
			$awo_storesId = $api_settings['storesId'];
			$awo_api_key_get = $api_settings['api_key_get'];
			
			// Получаем массив с настройками формы подписки на расслку проекта
			$subscribe_form_settings = unserialize($awo_settings->subscribe_form_settings);

			// Если передали настройки через параметры Шоткода
			if($id_newsletter != '')
			{
				$awo_id_newsletter = (int)$id_newsletter;
			}
			else
			{
				$awo_id_newsletter = (int)$subscribe_form_settings['id_newsletter']; 
			}
			
			// Если не передан код рассылки, то выводим текст ошибки
			if($awo_id_newsletter == 0)
			{
				return 'Неуказан код рассылки!';
			}
			
			// Если передали настройки через параметры Шоткода
			if($id_advertising_channel_page != '')
			{
				$awo_id_advertising_channel_page = (int)$id_advertising_channel_page;
			}
			else
			{
				$awo_id_advertising_channel_page = (int)$subscribe_form_settings['id_advertising_channel_page'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($last_name != '')
			{
				$awo_last_name = $last_name;
			}
			else
			{
				$awo_last_name = (bool)$subscribe_form_settings['last_name'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($name != '')
			{
				$awo_name = $name;
			}
			else
			{
				$awo_name = (bool)$subscribe_form_settings['name'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($middle_name != '')
			{
				$awo_middle_name = $middle_name;
			}
			else
			{
				$awo_middle_name = (bool)$subscribe_form_settings['middle_name'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($email != '')
			{
				$awo_email = $email;
			}
			else
			{
				$awo_email = (bool)$subscribe_form_settings['email'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($phone_number != '')
			{
				$awo_phone_number = $phone_number;
			}
			else
			{
				$awo_phone_number = (bool)$subscribe_form_settings['phone_number'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($policy_of_confidentiality != '')
			{
				$awo_policy_of_confidentiality = $policy_of_confidentiality;
			}
			else
			{
				$awo_policy_of_confidentiality = $subscribe_form_settings['policy_of_confidentiality'];
			}
			
			// Если передали настройки через параметры Шоткода
			if($subscribe_form_submit_value != '')
			{
				$awo_subscribe_form_submit_value = $subscribe_form_submit_value;
			}
			else
			{
				$awo_subscribe_form_submit_value = $subscribe_form_settings['subscribe_form_submit_value'];
			}
			
			// Надпись на кнопке не должна быть пустой
			if(trim($awo_subscribe_form_submit_value) == '')
			{
				$awo_subscribe_form_submit_value = 'Подписаться!';
			}

			
			if($id_newsletter == '')
			{
				$id_newsletter = $awo_id_newsletter;
			}
			
			if($id_advertising_channel_page == '')
			{
				$id_advertising_channel_page = $awo_id_advertising_channel_page;
			}
			
			if($last_name == '')
			{
				$last_name = $awo_last_name;
			}
			
			if($name == '')
			{
				$name = $awo_name;
			}
			
			if($middle_name == '')
			{
				$middle_name = $awo_middle_name;
			}
			
			if($email == '')
			{
				$email = $awo_email;
			}
			
			if($phone_number == '')
			{
				$phone_number = $awo_phone_number;
			}
			
			if($policy_of_confidentiality == '')
			{
				$policy_of_confidentiality = $awo_policy_of_confidentiality;
			}
			
			if($subscribe_form_submit_value == '')
			{
				$subscribe_form_submit_value = $awo_subscribe_form_submit_value;
			}
			
			$html_subscribe_form = '';
			
			// Подключаем файл сборки HTML-формы 
			include_once('html/html_subscribe_form.php');
			
			return $html_subscribe_form;
		}
		
		/**
		 * Сохраняем UTM-метки в сессию
		 */
		public function save_utm()
		{
			// Если в сессии нет UTM-меток и они переданы в запросе
			if(!isset($_SESSION['awo_utm']) and isset($_REQUEST['utm_source']) and isset($_REQUEST['utm_campaign']))
			{
				$utm['utm_source'] = $_REQUEST['utm_source']; // Кампания
				$utm['utm_campaign'] = $_REQUEST['utm_campaign']; // Рекламное объявление
				$utm['utm_term'] = $_REQUEST['utm_term']; // Ключевое слово
				$utm['utm_content'] = $_REQUEST['utm_content']; // Место размещения
				$utm['utm_medium'] = $_REQUEST['utm_medium']; // Тип источника трафика
				
				// Помещаем массив в сессию
				$_SESSION['awo_utm'] = $utm;
			}
		}
		
		/**
		 * Активация плагина
		 */
		function activate()
		{
			// Отвечает за запросы к базе данных
			global $wpdb;
			
			// Для работы с функцией dbDelta
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			
			## Определение версии mysql
			if(version_compare(mysql_get_server_info(), '4.1.0', '>=')) 
			{
				if(!empty($wpdb->charset))
				{
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				
				if(!empty($wpdb->collate))
				{
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			} 
			
			## Структура нашей таблицы для хранения информации о товарах магазина
			$sql_tbl_awo_goods = "
					CREATE TABLE `".$this->tbl_awo_goods."` (
                            `id_goods` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код товара',
                            `marking` varchar(255) NOT NULL COMMENT 'Артикул товара',
                            `in_affiliate` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Участвует в партнерке',
                            `show_in_affiliate` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Показывать в аккаунте партнера',
                            `goods` varchar(255) DEFAULT NULL COMMENT 'Название товара',
                            `variants_name` varchar(255) NOT NULL COMMENT 'Варианты названий',
                            `image` varchar(255) DEFAULT NULL COMMENT 'Основное изображение',
                            `url_external_image` varchar(255) NOT NULL COMMENT 'URL внешней картинки',
                            `url_external_image_used` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Использовать внешнюю картинку',
                            `brief_description` text COMMENT 'Краткое описание товара',
                            `price` decimal(10,2) DEFAULT '0.00' COMMENT 'Цена товара',
                            `price_purchase` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Цена закупки',
                            `url_page` varchar(255) NOT NULL,
                            `not_sold` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Товар не продается',
                            `new_of_sales` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Новинка продаж',
                            `hit_of_sales` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Хит продаж',
                            `id_goods_kind` int(11) NOT NULL COMMENT 'Код вида товара',
                            `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Товар удален',
                            `creation_date` datetime NOT NULL COMMENT 'Дата создания товара',
                            `order_fields` text NOT NULL COMMENT 'Настройки выводимых полей при заказе товара',
                            `information_single_order` longtext NOT NULL COMMENT 'Информация для оправки клиенту в одиночном заказе',
                            `information_cart_order` text NOT NULL COMMENT 'Информация для отправки клиенту в карзинном заказа',
                            `additional_order_fields` text NOT NULL,
                            `rest_in_stock` int(11) NOT NULL DEFAULT '0' COMMENT 'Остаток товара на складе',
                            `id_supplier` int(11) NOT NULL DEFAULT '0' COMMENT 'Код поставщика',
                            `id_manufacturer` int(11) NOT NULL DEFAULT '0' COMMENT 'Код производителя',
                            `id_employee_created` int(11) NOT NULL COMMENT 'Код сотрудника, создавшего товар',
                            `id_employee_deleted` int(11) NOT NULL COMMENT 'Код сотрудника, удалившего товар',
                            `deleted_date` datetime NOT NULL COMMENT 'Дата удаления',
                            `information_for_personal` longtext NOT NULL COMMENT 'Информация для личного кабинета',
                            `show_license_agreement` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Запрашивать лицензионное соглашение',
                            `partner_program_levels_used` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Использовать свои условия партнерской программы',
                            `partner_program_levels` text NOT NULL COMMENT 'Свои условия партнерской программы',
                            `special_offer` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Специальное предложение',
                            `awo_not_show` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Не показывать в каталоге',
                            `id_goods_category` int(11) NOT NULL DEFAULT '0' COMMENT 'Код категории товара',
                            `not_sold_message` text NOT NULL COMMENT 'Сообщение при попытке купить снятый с продажи товар',
                            `goods_color_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Название свойства Цвет',
                            `goods_size_name` varchar(255) NOT NULL COMMENT 'Название свойства Размер',
                            `goods_color_used` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Использовать свойство Цвет',
                            `goods_size_used` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Использовать свойство Размер',
						PRIMARY KEY  (`id_goods`),
						KEY `goods` (`goods`),
						KEY `id_supplier` (`id_supplier`),
						KEY `id_manufacturer` (`id_manufacturer`),
						KEY `marking` (`marking`),
						KEY `id_goods_category` (`id_goods_category`)
					)".$charset_collate." AUTO_INCREMENT=1;"; 
				
			## Проверка на существование таблицы Товары
			if($wpdb->get_var("SHOW TABLES LIKE `".$this->tbl_awo_goods."`") != $this->tbl_awo_goods)
			{
				// Анализирует текущую структуру таблицы, сравнивает ee с желаемой структурой таблицы, и либо добавляет или изменяет таблицу по мере необходимости
				dbDelta($sql_tbl_awo_goods);
			}
			
			
			## Структура нашей таблицы для хранения информации о категориях товаров магазина
			$sql_tbl_awo_goods_category = "
					CREATE TABLE `".$this->tbl_awo_goods_category."` (
							`id_goods_category` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код категории товара',
							`goods_category` varchar(255) NOT NULL COMMENT 'Категория товара',
							`brief_description` text NOT NULL COMMENT 'Краткое описание',
							`id_goods_category_parent` int(11) NOT NULL DEFAULT '0' COMMENT 'Код категории родителя',
							`id_employee_created` int(11) NOT NULL COMMENT 'Код сотрудника, создавшего расход',
							`id_employee_deleted` int(11) NOT NULL COMMENT 'Код сотрудника, удалившего расход',
							`deleted` tinyint(4) NOT NULL COMMENT 'Признак удаления',
							`deleted_date` datetime NOT NULL COMMENT 'Дата удаления',
							`creation_date` datetime NOT NULL COMMENT 'Дата создания',
						  PRIMARY KEY  (`id_goods_category`),
						  KEY `id_goods_category_parent` (`id_goods_category_parent`)
					)".$charset_collate." AUTO_INCREMENT=1;"; 
				
			## Проверка на существование таблицы Категории товаров
			if($wpdb->get_var("SHOW TABLES LIKE `".$this->tbl_awo_goods_category."`") != $this->tbl_awo_goods_category)
			{
				// Анализирует текущую структуру таблицы, сравнивает ee с желаемой структурой таблицы, и либо добавляет или изменяет таблицу по мере необходимости
				dbDelta($sql_tbl_awo_goods_category);
			}
			

			## Структура нашей таблицы для хранения настроек плагина
			$sql_tbl_awo_settings = "
					CREATE TABLE `".$this->tbl_awo_settings."` (
						`id_settings` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код строки настроек',
						`api_settings` text COMMENT 'Настройки подключений по API',
						`goods_update_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Дата обновления информации по товарам',
						`subscribe_form_settings` text NOT NULL COMMENT 'Настройки по умолчанию для формы подписки',
						`cart_settings` text NOT NULL COMMENT 'Настройки корзины заказа',
						`catalog_settings` text NOT NULL COMMENT 'Настройки отображения каталога товаров',
						`id_currency` int(11) DEFAULT '0' COMMENT 'Код валюты магазина',
						PRIMARY KEY  (`id_settings`)
					)".$charset_collate." AUTO_INCREMENT=2;"; 
				
			## Проверка на существование таблицы Настройки	
			if($wpdb->get_var("SHOW TABLES LIKE '$this->tbl_awo_settings'") != $this->tbl_awo_settings)
			{
				// Анализирует текущую структуру таблицы, сравнивает ee с желаемой структурой таблицы, и либо добавляет или изменяет таблицу по мере необходимости
				dbDelta($sql_tbl_awo_settings);
			}
			
			
			// Получаем данные по настройкам плагина
			$awo_settings = $wpdb->get_row("SELECT * FROM `".$this->tbl_awo_settings."` WHERE id_settings = 1");
			
			// Если таблица с настройками пуста, то добавляем одну строку
			if(!$awo_settings)
			{
				// Составляем массив со значениями полей
				$insertData = array(
							'id_settings' 		=> 1,	
							'api_settings' 		=> NULL,
							'goods_update_date' => '0000-00-00 00:00:00',						
				);
				
				// Указываем соответствующие форматы свтавляемых данных в столбцы
				$formatData = array('%d', '%s', '%s'); 
				
				$wpdb->insert($this->tbl_awo_settings, $insertData, $formatData);
			}
		}
		
		function deactivate() 
		{
			return true;
		}
		
		/**
		 * Удаление плагина
		 */
		function uninstall() 
		{
			global $wpdb;

			// Удаляем таблицы Товары
			$wpdb->query("DROP TABLE IF EXISTS `".$this->tbl_awo_goods."`");
			
			// Удаляем таблицы Категорий товара
			$wpdb->query("DROP TABLE IF EXISTS `".$this->tbl_awo_goods_category."`");
			
			// Удаляем таблицы Настройки
			$wpdb->query("DROP TABLE IF EXISTS `".$this->tbl_awo_settings."`");
		}
		
		/**
		 * Получаем валюту магазина в виде строки
		 */
		public function get_currency_str()
		{
			$awo_settings = $this->admin_get_settings();
			$curr = array('974' => 'BYR', 
						  '978'=>'EUR', 
						  '398'=>'KZT',
						  '987'=>'RUB',
						  '980'=>'UAH',
						  '840'=>'USD');
			return $curr[$awo_settings->id_currency];
		}
		
		
		public function get_url($key, $value)
		{
			return add_query_arg( $key, $value );
		}
        
        public function is_url_exist($url){
            $ch = curl_init($url);    
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if($code == 200){
               $status = true;
            }else{
              $status = false;
            }
            curl_close($ch);
           return $status;
        }
		
	}
}
 
global $rprice;
$rprice = new AutowebofficeInternetShop();
?>
