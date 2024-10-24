<?php
declare(strict_types=1);

namespace MRBS;

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

/*
 * get entry information by entry id
 * @Params
 * id：id of the entry which will be searched
 * @Return
 * entry information
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
