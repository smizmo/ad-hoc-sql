<?php

class AdHocSqlAdmin extends LeftAndMain implements PermissionProvider
{

    private static $url_segment = 'ad-hoc-sql';

    private static $menu_title = 'Ad Hoc SQL';

    private static $allowed_actions = array(
        'generateAction',
        'countAction',
        'sqlAction',
        'tableAction',
        'exportAction'
    );

    private static $model_importers = [];

    private static $require_explicit_permission = true;

    private static $search_fields = [
        'CheckboxField' => [
            'distinct' =>  'Distinct'
            ],
        'TextField' => [
            'select' => 'Select',
            'from' => 'From',
            'where' => 'Where',
            'whereAny' => 'Where Any',
            'leftJoin' => 'Left Join',
            'leftJoinClause' => 'Left Join Clause',
            'innerJoin' => 'Inner Join',
            'innerJoinClause' => 'Inner Join Clause',
            'having' => 'Having',
            'groupBy' => 'Group By',
            'orderBy' => 'Order By',
            'orderByDirection' => 'Order By Direction',
            'limit' => 'Limit'
        ]
    ];

    public function providePermissions()
    {
        return array(
            "Ad_Hoc_SQL" => "Ad hoc sql report admin."
        );
    }

    public static function set_require_explicit_permission($val)
    {
        self::$require_explicit_permission = $val;
    }

    public static function get_require_explicit_permission($val)
    {
        return self::$require_explicit_permission;
    }

    public function init()
    {
        parent::init();

        Requirements::css('ad_hoc_sql/css/adhocsql.css');
        Requirements::javascript('ad_hoc_sql/js/adHocSqlAdmin.js');

        if (self::$require_explicit_permission && !Permission::check("Ad_Hoc_SQL")) {
            Security::permissionFailure();
        }
    }

    private static $menu_priority = -0.5;

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::EmptyForm($id, $fields);
        $form->addExtraClass('center');

        foreach ( self::$search_fields as $type => $fields){
            if ($type === 'CheckboxField'){
                foreach ($fields as $name => $title){
                    $field = new CheckboxField(sprintf('q[%s]', $name), $title);
                    $form->Fields()->push($field);
                }
            } else if($type === 'TextField'){
                foreach ($fields as $name => $title){
                    $field = new TextField(sprintf('q[%s]', $name), $title);
                    $form->Fields()->push($field);
                }
            }
        }
        $form->loadDataFrom($this->getRequest()->postVars());

        $actions = $form->Actions();
        $actions->push(FormAction::create('generateAction', 'Apply'));


        $params = $this->request->requestVar('q'); // use this to access search parameters

        $actions->push(FormAction::create('sqlAction', 'Sql'));
        $actions->push(FormAction::create('countAction', 'Count'));
        $actions->push(FormAction::create('tableAction', 'Table'));
        $button = FormAction::create('exportAction', 'Export');
        $button->addExtraClass('no-ajax-ad-hoc');
        //$actions->push($button);

        return $form;
    }

    public function generateQuery($count = false)
    {
        $sqlQuery = new SQLSelect();

        $params = $this->request->requestVar('q');
        if (isset($params['distinct']) && $params['distinct']) {
            $sqlQuery->setDistinct(true);
        }

        if (isset($params['select']) && $params['select']) {
            if($count){
                $sqlQuery->setSelect('COUNT(*)');
            } else {
                $sqlQuery->setSelect($params['select']);
            }
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

        if (isset($params['having']) && $params['having']) {
            $sqlQuery->setHaving($params['having']);
        }

        if (isset($params['groupBy']) && $params['groupBy']) {
            $sqlQuery->setGroupBy($params['groupBy']);
        }

        if (isset($params['orderBy']) && $params['orderBy']) {
            $sqlQuery->setOrderBy($params['orderBy'], isset($params['orderByDirection']) && $params['orderByDirection'] ? $params['orderByDirection'] : '');
        }

        if (!$count && isset($params['limit']) && $params['limit']) {
            $sqlQuery->setLimit($params['limit']);
        }

        return $sqlQuery;
    }

    public function doQuery(SQLSelect $sqlQuery)
    {
        try {
            $query = $sqlQuery->execute();
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
        return $query;
    }

    public function getResult()
    {
        $params = $this->request->requestVar('q'); // use this to access search parameters

        $result = '';

        if ($params) {
            if ($this->request->requestVar('action_countAction')) {
                $result = $this->doQuery($this->generateQuery(true))->table();
            } else if ($this->request->requestVar('action_tableAction') == 1) {
                $result = $this->doQuery($this->generateQuery())->table();
            } else {
                $result = $this->generateQuery()->sql();
            }
        }
        return $result;
    }

    public function handleExport() {
        $now = Date("d-m-Y-H-i");
        $fileName = "export-$now.csv";

        if($fileData = $this->generateExportFileData()){
            return SS_HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
    }

    public function generateExportFileData() {
        $separator = ",";
        $fileData = array();
        $headerRow = true;

        foreach ($this->doQuery($this->generateQuery()) as $data) {
            if ($headerRow) {
                $headers = [];
                foreach($data as $field => $value){
                    $headers[] = $field;
                }
                $fileData[] = $headers;
                $headerRow = false;
            }
            $columnData = [];
            foreach($data as $field => $value){
                $columnData[] = $value;
            }

            $fileData[] = $columnData;
        }

        // Convert the $fileData array into csv by capturing fputcsv's output
        $csv = fopen('php://temp', 'r+');
        foreach($fileData as $line) {
            fputcsv($csv, $line, $separator);
        }
        rewind($csv);
        return stream_get_contents($csv);
    }

    public function generateAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function sqlAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function countAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function tableAction($data, Form $form)
    {
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    public function exportAction($data, Form $form)
    {
        return $this->handleExport();
    }
}
