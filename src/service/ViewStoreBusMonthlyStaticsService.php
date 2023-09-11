<?php

namespace xjryanse\store\service;

use xjryanse\system\interfaces\MainModelInterface;
use xjryanse\logic\Arrays;

/**
 * 
 */
class ViewStoreBusMonthlyStaticsService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\store\\model\\ViewStoreBusMonthlyStatics';

}
