<?php
session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}
includeModule('cache');
includeModule('order');

$offset = 0;
if (array_key_exists('offset', $_GET)) {
    $offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
    if ($offset === false) {
        $offset = 0;
    }
}

var_dump($_SERVER['order'], $_SERVER['type'], $offset);

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>oRDR</title>
    <link rel='stylesheet' href='http://cdn.ordr.alxmsl.stage/css/bootstrap.min.css' type='text/css' media='all' />
    <script src="http://cdn.ordr.alxmsl.stage/js/jquery-2.1.4.min.js"></script>
    <script src="http://cdn.ordr.alxmsl.stage/js/bootstrap.min.js"></script>
    <script type="application/javascript">
        $(document).ready(function() {
            $('#profileButton').on('click', function () {
                $(location).attr('href', "/profile");
            });

            $("#orderBtn").on("click", function() {
                $(location).attr('href', "/profile/date/<?= $_SERVER['type'] == 'asc' ? 'desc' : 'asc' ?>");
            });

            $("#typeBtn").on("click", function() {
                $(location).attr('href', "/profile/price/<?= $_SERVER['type'] == 'asc' ? 'desc' : 'asc' ?>");
            });

            $("#prevBtn").on("click", function () {
                if (!$(this).hasClass("disabled")) {
                    $(location).attr('href', "/profile/<?= $_SERVER['order'] ?>/<?= $_SERVER['type'] ?>?offset=<?= $offset - ORDERS_PER_PAGE?>");
                } else {
                    return false;
                }
            });

            $("#nextBtn").on("click", function () {
                if (!$(this).hasClass("disabled")) {
                    $(location).attr('href', "/profile/<?= $_SERVER['order'] ?>/<?= $_SERVER['type'] ?>?offset=<?= $offset + ORDERS_PER_PAGE?>");
                } else {
                    return false;
                }
            });

            Date.prototype.YmdHis = function() {
                var Y = this.getFullYear().toString();
                var m = (this.getMonth()+1).toString();
                var d = this.getDate().toString();
                var h = this.getHours().toString();
                var i = this.getMinutes().toString();
                var s = this.getSeconds().toString();
                return Y + "-" + (m[1] ? m : "0" + m[0]) + "-" + (d[1] ? d : "0" + d[0])
                    + " " + (h[1] ? h : "0" + h[0]) + ":" + (i[1] ? i : "0" + i[0]) + ":" + (s[1] ? s : "0" + s[0]);
            };

            <?php if($_SESSION['profile']['type'] == 1): ?>
                setInterval (function() {
                    $.ajax({
                        url: "/cache?order=<?= $_SERVER['order'] ?>&type=<?= $_SERVER['type'] ?>&offset=<?= $offset ?>",
                        type: "GET",
                        dataType: "json",
                        success:function(result) {
                            if (result.orders) {
                                $(".table > tbody").empty();
                                for (var i in result.orders) {
                                    var updated = new Date(result.orders[i].updated * 1000);
                                    $(".table > tbody").append('<tr id="row' + result.orders[i].order_id + '">'
                                        + '<td>' + result.orders[i].description + '</td>'
                                        + '<td>' + result.orders[i].price + '</td>'
                                        + '<td>' + updated.YmdHis() + '</td>'
                                        + '<td>'
                                        + '<button type="button" data-order="' + result.orders[i].order_id + '" data-owner="' + result.orders[i].customer_id
                                            + '" class="executeBtn btn btn-default" data-dismiss="modal">выполнить</button>'
                                        + '</td>'
                                     + '</tr>');
                                }
                            }
                        }
                    });
                }, 5000);

                setInterval (function() {
                    $.ajax({
                        url: "/session",
                        type: "GET",
                        dataType: "json",
                        success:function(result) {
                            if (result.session) {
                                $("#salaryCnt").text(result.session.money);
                            }
                        }
                    });
                }, 10000);
            <?php endif ?>

            $('.editBtn').on('click', function () {
                $(location).attr('href', "/order/" + $(this).data("order"));
            });

            $('.deleteBtn').on('click', function () {
                var orderId = $(this).data("order")
                $.ajax({
                    url: "/order/" + orderId,
                    type: "DELETE",
                    dataType: "json",
                    success:function(result) {
                        if (result.completed) {
                            $("tr#row" + orderId).remove();
                            $("#completeModal .modal-body").text("Заказ успешно удален");
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
            });

            $(document).on('click', '.executeBtn', function () {
                var orderId = $(this).data("order");
                var ownerId = $(this).data("owner");
                $.ajax({
                    url: "/order/" + orderId + "/" + ownerId,
                    type: "PUT",
                    dataType: "json",
                    success:function(result) {
                        if (result.completed) {
                            $("tr#row" + orderId).remove();
                            $("#completeModal .modal-body").text("Заказ выполнен!");
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
                <?php if ($_SESSION['profile']['type'] == 0): ?>
                    <li><a href="/order">Создать заказ</a></li>
                    <li class="divider"></li>
                <?php else: ?>
                    <li>&nbsp;Доход: <span id="salaryCnt"><?= $_SESSION['profile']['money'] ?: 0 ?></span></li>
                    <li class="divider"></li>
                <?php endif ?>
                <li><a href="/logout">Выйти</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-2">&nbsp;</div>
    <div class="col-sm-6">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Описание</th>
                <th><button id="typeBtn" class="btn-link">Стоимость</button></th>
                <th><button id="orderBtn" class="btn-link">Дата</button></th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
                <?php
                    $orders = ($_SESSION['profile']['type'] == 0)
                        ? order_get_all($_SESSION['profile']['user_id'], $_SERVER['order'], $_SERVER['type'], $offset)
                        : cache_get($_SERVER['order'], $_SERVER['type'], $offset);
                    foreach($orders as $order):
                ?>
                    <tr id="row<?= $order['order_id'] ?>">
                        <td><?= $order['description'] ?></td>
                        <td><?= $order['price'] ?></td>
                        <td><?= date('Y-m-d H:i:s', $order['updated']) ?></td>
                        <td>
                            <?php if($_SESSION['profile']['type'] == 0): ?>
                                <button type="button" data-order="<?= $order['order_id'] ?>" class="editBtn btn btn-default" data-dismiss="modal">редактировать</button>
                                <button type="button" data-order="<?= $order['order_id'] ?>" class="deleteBtn btn btn-default" data-dismiss="modal">удалить</button>
                            <?php else: ?>
                                <button type="button" data-order="<?= $order['order_id'] ?>" data-owner="<?= $order['customer_id'] ?>" class="executeBtn btn btn-default" data-dismiss="modal">выполнить</button>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <td><button id="prevBtn" type="button" class="btn btn-default <?= ($offset < ORDERS_PER_PAGE) ? 'disabled' : '' ?>"><<</button></td>
                    <td><button id="nextBtn" type="button" class="btn btn-default <?= count($orders) < ORDERS_PER_PAGE ? 'disabled' : '' ?>"> >> </button></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
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
                <h4 class="modal-title" id="myModalLabel">Поздравляю!</h4>
            </div>
            <div class="modal-body">
                Ваш заказ успешно удален
            </div>
            <div class="modal-footer">
                <button type="button" id="loginBtn" class="btn btn-primary" data-dismiss="modal">Спасибо</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
