<?php

/**
 * Class AdHocSqlAdmin
 */
class AdHocSqlAdmin extends ModelAdmin implements PermissionProvider
{

    /**
     * @var string
     */
    private static $url_segment = 'ad-hoc-sql';

    /**
     * @var string
     */
    private static $menu_title = 'Ad Hoc';

    /**
     * @var string[]
     */
    private static $managed_models = [
        AdHocSQL::class
    ];

    /**
     * @var string[]
     */
    private static $allowed_actions = array(
        'countAction',
        'sqlAction',
        'stableAction',
        'exportAction'
    );

    /**
     * @var array
     */
    private static $model_importers = [];

    /**
     * @var bool
     */
    private static $require_explicit_permission = true;

    /**
     * @return string[]
     */
    public function providePermissions()
    {
        return array(
            "Ad_Hoc_SQL" => "Ad hoc sql report admin."
        );
    }

    /**
     * @param $val
     */
    public static function set_require_explicit_permission($val)
    {
        self::$require_explicit_permission = $val;
    }

    /**
     * @param $val
     * @return bool
     */
    public static function get_require_explicit_permission($val)
    {
        return self::$require_explicit_permission;
    }

    /**
     *
     */
    public function init()
    {
        parent::init();

        if (self::$require_explicit_permission && !Permission::check("Ad_Hoc_SQL")) {
            Security::permissionFailure();
        }
    }

    /**
     * @var float
     */
    private static $menu_priority = -0.5;

    /**
     * @param null $id
     * @param null $fields
     * @return mixed
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $gridFieldName = $this->sanitiseClassName($this->modelClass);
        $form->Fields()->removeByName($gridFieldName);

        $params = $this->request->requestVar('q'); // use this to access search parameters

        if ($params) {
            $actions = $form->Actions();
            $actions->push(FormAction::create('sqlAction', 'Sql'));
            $actions->push(FormAction::create('countAction', 'Count'));
            $actions->push(FormAction::create('tableAction', 'Table'));
            $actions->push(FormAction::create('exportAction', 'Export'));
            $query = $this->generateQuery();
            foreach($params as $key => $value){
                $form->Fields()->push(HiddenField::Create("q[".$key."]",$key,$value));
            }
            if ($this->request->requestVar('action_countAction')) {
                $form->Fields()->push(LiteralField::Create("Count",$this->doQuery($this->generateQuery())->numRecords()));
            } else if ($this->request->requestVar('action_tableAction') == 1) {
                $form->Fields()->push(LiteralField::Create("Results",$this->doQuery($this->generateQuery())->table()));
            } else {
                $form->Fields()->push(LiteralField::Create("Sql",$query->sql()));
            }
        }

        return $form;
    }

    /**
     * @return SQLSelect
     */
    public function generateQuery()
    {
        $sqlQuery = new SQLSelect();

        $params = $this->request->requestVar('q');
        if (isset($params['distinct']) && $params['distinct']) {
            $sqlQuery->setDistinct(true);
        }

        if (isset($params['select']) && $params['select']) {
            $sqlQuery->setSelect($params['select']);
        }

        if (isset($params['from']) && $params['from']) {
            $sqlQuery->setFrom($params['from']);
        }

        if (isset($params['where']) && $params['where']) {
            $sqlQuery->setWhere($params['where']);
        } else if (isset($params['whereAny']) && $params['whereAny']) {
            $sqlQuery->setWhereAny($params['whereAny']);
        }

        if (isset($params['leftJoin']) && $params['leftJoin']) {
            $sqlQuery->addLeftJoin($params['leftJoin'],isset($params['leftJoinClause']) && $params['leftJoinClause'] ? $params['leftJoinClause'] : '');
        } else if (isset($params['innerJoin']) && $params['innerJoin']) {
            $sqlQuery->addInnerJoin($params['innerJoin'],isset($params['innerJoinClause']) && $params['innerJoinClause'] ? $params['innerJoinClause'] : '');
        }

        if (isset($params['groupBy']) && $params['groupBy']) {
            $sqlQuery->setGroupBy($params['groupBy']);
        }

        if (isset($params['orderBy']) && $params['orderBy']) {
            $sqlQuery->setOrderBy($params['orderBy'], isset($params['orderByDirection']) && $params['orderByDirection'] ? $params['orderByDirection'] : '');
        }

        if (isset($params['limit']) && $params['limit']) {
            $sqlQuery->setLimit($params['limit']);
        }

        return $sqlQuery;
    }

    /**
     * @param SQLSelect $sqlQuery
     * @return string
     */
    public function doQuery(SQLSelect $sqlQuery)
    {
        try {
            $query = $sqlQuery->execute();
        } catch (Exception $exception) {
            SS_Log::log(
                sprintf('DateTime (%s)', $exception->getMessage()),
                SS_Log::ERR
            );
            return $exception->getMessage();
        }
        return $query;
    }

    /**
     * @param $data
     * @param Form $form
     * @return mixed
     */
    public function sqlAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @param $data
     * @param Form $form
     * @return mixed
     */
    public function countAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @param $data
     * @param Form $form
     * @return mixed
     */
    public function tableAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    /**
     * @return mixed
     */
    public function getSearchContext()
    {
        $context = parent::getSearchContext();

        $context->getFields()->removeByName('q[ID]');

        $distinct = new CheckboxField('q[distinct]', 'Distinct');
        $context->getFields()->push($distinct);

        $select = new TextField('q[select]', 'Select');
        $context->getFields()->push($select);

        $from = new TextField('q[from]', 'From');
        $context->getFields()->push($from);

        $where = new TextField('q[where]', 'Where');
        $context->getFields()->push($where);

        $whereAny = new TextField('q[whereAny]', 'Where Any');
        $context->getFields()->push($whereAny);

        $leftJoin = new TextField('q[leftJoin]', 'Left Join');
        $context->getFields()->push($leftJoin);

        $leftJoinClause = new TextField('q[leftJoinClause]', 'Left Join Clause');
        $context->getFields()->push($leftJoinClause);

        $innerJoin = new TextField('q[innerJoin]', 'Inner Join');
        $context->getFields()->push($innerJoin);

        //"TEATransaction"
        // ON "TEATransaction"."SenderAccountNumber" = "MemberBalance"."SenderAccountNumber"
//"MemberBalance"."Contributions"
        //"MemberBalance"
        $leftJoinClause = new TextField('q[innerJoinClause]', 'Inner Join Clause');
        $context->getFields()->push($leftJoinClause);

        $groupBy = new TextField('q[groupBy]', 'Group By');
        $context->getFields()->push($groupBy);

        $orderBy = new TextField('q[orderBy]', 'Order By');
        $context->getFields()->push($orderBy);

        $orderByDirection = new TextField('q[orderByDirection]', 'Order By Direction');
        $context->getFields()->push($orderByDirection);

        $limit = new TextField('q[limit]', 'Limit');
        $context->getFields()->push($limit);

        return $context;
    }
}
