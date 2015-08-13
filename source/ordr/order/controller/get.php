<?php
/**
 * Страница добавления и редактирования заказа
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: /index.html');
    exit(0);
}

// Функционал данной страницы доступен только заказчикам
if ($_SESSION['profile']['type'] != 0) {
    header('Location: /profile');
    exit(0);
}

/**
 * Проверка ОДЗ на идентификатор редактируемого заказа
 * Если проверка не была произведена успешно, считаем, что происходит добавление нового заказа
 */
$order = null;
if (array_key_exists('order', $_SERVER)) {
    $orderId = filter_var($_SERVER['order'], FILTER_VALIDATE_INT);
    if ($orderId !== false) {
        includeModule('order');
        $order = order_get($orderId, $_SESSION['profile']['user_id']);
        if ($order === false) {
            $order = null;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>oRDR</title>
    <link rel='stylesheet' href='http://cdn.ordr.alxmsl.stage/css/bootstrap.min.css' type='text/css' media='all' />
    <script src="http://cdn.ordr.alxmsl.stage/js/jquery-2.1.4.min.js"></script>
    <script src="http://cdn.ordr.alxmsl.stage/js/bootstrap.min.js"></script>
    <script src="http://cdn.ordr.alxmsl.stage/js/validator.min.js"></script>
    <script type="application/javascript">
        $(document).ready(function() {
            $("#createOrderForm").validator().on('submit', function (e) {
                if (!e.isDefaultPrevented()) {
                    e.preventDefault();
                    $.ajax({
                        url: "/order<?= (!is_null($order)) ? sprintf('/%s', $order['order_id']) : '' ?>",
                        type: "POST",
                        crossDomain: true,
                        data: $("#createOrderForm").serialize(),
                        dataType: "json",
                        success:function(result) {
                            if (result.completed) {
                                $('#completeModal').modal('show');
                            } else if (result.error) {
                                $("#errorModal .modal-body").text("service error: " + result.error);
                                $('#errorModal').modal('show');
                            } else {
                                $("#errorModal .modal-body").text("unknown response: " + JSON.stringify(result));
                                $('#errorModal').modal('show');
                            }
                        },
                        error:function(xhr, status, error){
                            $("#errorModal .modal-body").text("error: " + status);
                            $('#errorModal').modal('show');
                        }
                    });
                }
            });

            $('#ordersBtn').on('click', function () {
                $(location).attr('href', "/profile");
            });

            $('#profileButton').on('click', function () {
                $(location).attr('href', "/profile");
            });
        });
    </script>
</head>
<body>
<div class="row">&nbsp</div>

<div class="row">
    <div class="col-sm-4">&nbsp;</div>
    <div class="col-sm-4">&nbsp;</div>
    <div class="col-sm-4">
        <div class="btn-group">
            <button type="button" id="profileButton" class="btn btn-info"><?= $_SESSION['profile']['login'] ?></button>
            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Меню с переключением</span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="/order">Создать заказ</a></li>
                <li class="divider"></li>
                <li><a href="/logout">Выйти</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-4">&nbsp;</div>
    <div class="col-sm-4">
        <form role="form" id="createOrderForm" data-toggle="validator">
            <div class="form-group">
                <label for="description">Описание</label>
                <input type="text" class="form-control" name="description" id="description" placeholder="Описание заказа"
                       pattern="^[0-9A-Za-zА-Яа-я\s\-]{5,}$"
                       maxlength="20"
                       data-error="Описание заказа должно быть не более 20 символов. Допустимы советские и латинский буквы, цифры, символ пробела"
                       required
                       <?php if (!is_null($order)): ?>
                            value="<?= $order['description'] ?>"
                       <?php endif ?>
                    />
                <div class="help-block with-errors"></div>
            </div>
            <div class="form-group">
                <label for="price">Стоимость</label>
                <input type="number" class="form-control" name="price" id="price" placeholder="Стоимость"
                       min="100"
                       data-error="Стоимость заказа обязательна. Минимальная стоимость - 100"
                       required
                    <?php if (!is_null($order)): ?>
                        value="<?= (int) $order['price'] ?>"
                    <?php endif ?>
                    />
                <div class="help-block with-errors"></div>
            </div>
            <?php if (is_null($order)): ?>
                <button type="submit" class="btn btn-success">Опубликовать</button>
            <?php else: ?>
                <button type="submit" class="btn btn-success">Сохранить</button>
            <?php endif ?>
        </form>
    </div>
    <div class="col-sm-4">&nbsp;</div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="myErrorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myErrorModalLabel">Ошибка</h4>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">жаль</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Заказ сохранен</h4>
            </div>
            <div class="modal-body">
                Заказ опубликован. Хотите просмотреть все свои заказы?
            </div>
            <div class="modal-footer">
                <button type="button" id="ordersBtn" class="btn btn-primary" data-dismiss="modal">Конечно</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
