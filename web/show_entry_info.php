<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

/*
 * 根据id查询会议信息
 * @Params
 * id：待查询会议的id
 * @Return
 * data中包含该会议的信息
 */

$id = intval($_POST['id']);

if (!isset($id) || $id === '' || $id == 0) {
  ApiHelper::fail(get_vocab("search_without_id"), ApiHelper::SEARCH_WITHOUT_ID);
}

$result = db() -> query("SELECT * FROM " . _tbl("entry") . " WHERE id = ?", array($id));
if ($result -> count() < 1){
  ApiHelper::fail(get_vocab("entry_not_exist"), ApiHelper::ENTRY_NOT_EXIST);
}

ApiHelper::success($result->next_row_keyed());
