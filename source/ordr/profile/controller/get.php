<?php
session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}
includeModule('cache');
includeModule('order');
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

            $('.executeBtn').on('click', function () {
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
                <?php if ($_SESSION['profile']['type'] == 0): ?>
                    <li><a href="/order">Создать заказ</a></li>
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
                <th>Стоимость</th>
                <th>Дата добавления</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <?php
                $orders = ($_SESSION['profile']['type'] == 0) ? order_get_all($_SESSION['profile']['user_id']) : cache_get();
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
                            <button type="button" data-order="<?= $order['order_id'] ?>" class="executeBtn btn btn-default" data-dismiss="modal">выполнить</button>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
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
