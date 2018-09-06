<?

// подключаем bitrix core
if (empty($_SERVER["DOCUMENT_ROOT"]))
    $_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$users = array();

// подключаем модуль SALE
if (CModule::IncludeModule("sale")) {

	// формируем массив отложенных товаров
    $rsBaskets = CSaleBasket::GetList(
        array(
            "USER_ID" => "ASC",
            "PRODUCT_ID" => "ASC"
        ),
        array(
            'DELAY' => 'Y'
        ),
        false,
        false,
        array("ID", "PRODUCT_ID", "NAME", "QUANTITY", "DELAY", "USER_ID")
    );

	// формируем массив пользователи + отложенные товары
    while ($arBaskets = $rsBaskets->Fetch()) {

        $users[$arBaskets['USER_ID']]['USER_ID'] = $arBaskets['USER_ID'];
        $users[$arBaskets['USER_ID']]['products'][$arBaskets['PRODUCT_ID']] = array($arBaskets['NAME'], $arBaskets['QUANTITY']);

    }
	// собираем заказы пользователя за последние 30 дней
    foreach ($users as $id_user => $user) {
        $rsUserLastOrders = CSaleOrder::GetList(
            array('DATE_INSERT' => 'DESC'),
            array(
                "USER_ID" => $user["USER_ID"],
                ">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), time() - 86400 * 30)
            ),
            false,
            false,
            array("*")
        );

		// если пользователь оформлял заказы в течение 30 дней
        if ($rsUserLastOrders->SelectedRowsCount()) {

            while ($arUserLastOrders = $rsUserLastOrders->Fetch()) {

                $rsOrder = CSaleBasket::GetList(array(), array('ORDER_ID' => $arUserLastOrders['ID']));
                while ($arOrder = $rsOrder->Fetch()) {
					
					// ищем ID отложенного товара в массиве заказанных товаров
                    if (array_key_exists($arOrder['PRODUCT_ID'], $users[$id_user]['products'])) {
						//удаляем из массива отложенных товаров
                        unset($users[$id_user]['products'][$arOrder['PRODUCT_ID']]);
                    }
                }
            }
        }
    }

	// шаблон письма для простой отправки
    $template_message = "Добрый день, #NAME#. В вашем листе хранятся товары: \r\n
#LIST_DELAY_PRODUCT#";
	
	// отправляем письма
    foreach ($users as $id_user => $data) {
        if (count($data['products'])) {
			//получаем информацию о пользователе
            if ($user_data = CUser::GetByID($id_user)->Fetch()) {
                $users[$id_user]['NAME'] = $user_data['NAME'] . ' ' . $user_data['LAST_NAME'];
                $users[$id_user]['EMAIL'] = $user_data['EMAIL'];
            }
            $products_list = '';
            foreach ($data['products'] as $product) {
                $products_list .= $product[0] . ' - ' . $product[1] . " шт.\r\n";
            }

            // отправляем с использованием шаблона
            $arEventFields = array(
                "USER_NAME" => $users[$id_user]['NAME'],
                "EMAIL_TO" => $users[$id_user]['EMAIL'],
                "LIST_DELAY_PRODUCT" => $products_list
            );

            CEvent::Send("CHM_SAVED_ITEMS", 's1', $arEventFields);


			//простая отправка письма (без HTML-шаблона)
            /*$message = preg_replace(
            array('/#NAME#/i', '/#LIST_DELAY_PRODUCT#/i'),
            array($users[$id_user]['NAME'], $products_list),
            $template_message
            );
            bxmail(
            $users[$id_user]['EMAIL'],
            'Отложенные товары из магазина для '. $users[$id_user]['NAME'],
            $message,
            "Content-type: text/plain; charset=utf-8\r\n"
            );*/
        }
    }
} else {
    echo "Не подключился модуль sale";
}

?>