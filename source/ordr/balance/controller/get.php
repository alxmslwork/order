<?php
/**
 * Метод API получения баланса системы
 * @author alxmsl
 */

includeModule('balance');
return [
    'balance' => balance_get(),
];
