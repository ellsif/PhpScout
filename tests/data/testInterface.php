<?php

namespace ellsif;

/**
 * データアクセスインタフェース
 *
 * ## 説明
 * DB、ファイルなどシステムで利用するデータにアクセスするためのインタフェースを定義します。<br>
 * DataAccessクラスはEntityクラスからの利用を想定しています。
 */
interface DataAccess
{

    /**
     * テーブルを作成する。
     *
     * ## 説明
     * 作成されるテーブルには自動的にid、createdAt、updatedAtカラムが追加されます。
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。テーブル名は先頭を大文字としたキャメルケース、単数形を推奨します。</dd>
     *   <dt>columns</dt>
     *     <dd>カラム名と型情報の連想配列を指定します。</dd>
     * </dl>
     *
     * ## 返り値
     * 成功した場合にtrueを、失敗した場合にfalseを返します。
     *
     * ## 例
     * id、createdAt、updatedAtは自動で付加されるため、指定できません。
     *
     *     $dataAccess->createTable('user', array(
     *       'name' => 'TEXT',
     *       'email' => 'TEXT DEFAULT',
     *       'number' => 'INTEGER DEFAULT 1',
     *     ));
     */
    public function createTable(string $name, array $columns) :bool;

    /**
     * テーブルを削除する
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>force</dt>
     *     <dd>trueを指定した場合、nameで指定されたテーブルがWelCoMeSの標準テーブルであっても削除を実行します。</dd>
     * </dl>
     *
     * ## 返り値
     * 成功した場合にtrueを、失敗した場合にfalseを返します。
     *
     */
    public function deleteTable(string $name, bool $force = false) :bool;

    /**
     * 件数を取得する。
     */
    public function count(string $name, array $filter = []);

    /**
     * id指定で1件取得する。
     *
     * ## 説明
     * idを指定して1件取得します。
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>id</dt>
     *     <dd>idを指定します。</dd>
     * </dl>
     *
     * ## 返り値
     * データの連想配列を返します。該当データが見つからない場合はnullを返します。
     *
     * ## 例外/エラー
     * 取得に失敗した場合、Exceptionをthrowします。（該当するidのデータが存在しない場合はnullを返して正常処理として扱います）
     */
    public function get(string $name, int $id);

    /**
     * 複数件取得する。
     *
     * ## 説明
     * 簡単な取得条件でデータを指定します。複雑な条件で取得したい場合はselectQuery()を利用してください。
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>offset</dt>
     *     <dd>offsetを指定します。</dd>
     *   <dt>limit</dt>
     *     <dd>limitを指定します。</dd>
     *   <dt>order</dt>
     *     <dd>orderを指定します。</dd>
     *   <dt>options</dt>
     *     <dd>
     *       where条件に当たる部分です。連想配列で指定します。<br>
     *       IN句を指定する場合は値として配列を指定します。
     *     </dd>
     * </dl>
     *
     * ## 返り値
     * データの連想配列からなる配列を返します。該当データが見つからない場合は空の配列を返します。
     *
     * ## 例外/エラー
     * 取得に失敗した場合、Exceptionをthrowします。
     *
     * ## 例
     * 10件づつ取得する場合。
     *
     *     $dataAccess = getDataAccess();
     *     $offset = 0;
     *     $limit = 10;
     *     while (true) {
     *       $data = $dataAccess->select(
     *         'user',
     *         $offset,
     *         $limit,
     *         [
     *           'role' => ['admin', 'sys_admin'],  // WHERE role IN ('admin', 'sys_admin')
     *           'name LIKE' => 'Yamada %',          // AND name LIKE 'Yamada %'
     *         ]
     *       );
     *       var_dump($data);  // [['id'=>1, 'name'=>'Yamada Taro', 'role'=>'admin'],['id'=>2, 'name'=>'Yamada Jiro', 'role'=>'sys_admin'],...]
     *       if (count($data) < $limit) {
     *         break;
     *       }
     *       $offset += $limit;
     *     }
     */
    public function select(string $name, int $offset = 0, int $limit = -1, string $order = '', array $options = []) :array;

    /**
     * SQL文による検索
     *
     * ## 説明
     * SQLを実行し、取得結果を返します。
     *
     * ## パラメータ
     * <dl>
     *   <dt>sql</dt>
     *     <dd>SQL文を指定します。</dd>
     *   <dt>params</dt>
     *     <dd>SQL文にバインドする値の配列、または連想配列。</dd>
     * </dl>
     *
     * ## 例
     * 名前付けされたプレースホルダを用いてSQLを実行する。
     *
     *     $dataList = $dataAccess->selectQuery(
     *       'SELECT content.* FROM contents LEFT JOIN page_content ON (content.id = page_content.content_id) WHERE page_content.page_id = :id',
     *       ['id' => 1]
     *     );
     * 疑問符プレースホルダを用いてSQLを実行する。
     *
     *     $dataList = $dataAccess->selectQuery(
     *       'SELECT content.* FROM contents LEFT JOIN page_content ON (content.id = page_content.content_id) WHERE page_content.page_id = ?',
     *       [1]
     *     );
     */
    public function selectQuery(string $sql, array $params = []): array;


    /**
     * データ1件を登録/更新する
     *
     * ## 説明
     * 1件分の登録処理を行います。data引数でidが指定されている場合は更新処理を行います。
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名。</dd>
     *   <dt>data</dt>
     *     <dd>登録/更新用データを連想配列で指定します。</dd>
     * </dl>
     *
     * ## 返り値
     * 処理実行後のデータ（登録処理の場合はidが設定されます）を返します。<br>
     * idに指定があり、該当データが存在しない場合、falseを返します。
     *
     * ## 例外/エラー
     * クエリ自体が失敗した場合、Exceptionをthrowします。
     */
    public function save(string $name, array $data);

    /**
     * データを1件登録する。
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>data</dt>
     *     <dd>登録用データを連想配列で指定します。</dd>
     * </dl>
     *
     * ## 返り値
     * 登録したデータのidを返します。
     *
     * ## 例外/エラー
     * クエリ自体が失敗した場合、Exceptionをthrowします。
     */
    public function insert(string $name, array $data) :int;

    /**
     * データを1件更新する
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>id</dt>
     *     <dd>idを指定します。</dd>
     *   <dt>data</dt>
     *     <dd>更新用データを連想配列で指定します。配列に含まれる項目のみ更新対象となります。</dd>
     * </dl>
     *
     * ## 返り値
     * 実行結果を返します。
     *
     * ## 例外/エラー
     * クエリ自体が失敗した場合、Exceptionをthrowします。
     */
    public function update(string $name, int $id, array $data) :bool;

    /**
     * 複数件更新する
     *
     * ## パラメータ
     * <dl>
     *   <dt>name</dt>
     *     <dd>テーブル名を指定します。</dd>
     *   <dt>data</dt>
     *     <dd>更新用データを連想配列で指定します。配列に含まれる項目のみ更新対象となります。</dd>
     *   <dt>condition</dt>
     *     <dd>更新の条件（WHERE句）を指定します。</dd>
     * </dl>
     *
     * ## 返り値
     * 更新されたレコード数を返します。
     *
     * ## 例外/エラー
     * クエリ自体が失敗した場合、Exceptionをthrowします。
     */
    public function updateAll(string $name, array $data, array $condition) :int;

    /**
     * 1件削除する
     *
     * @param string $name
     * @param int $id
     * @return bool
     */
    public function delete(string $name, int $id) :bool;

    /**
     * 複数件削除する
     *
     * @param string $name
     * @param array $condition
     * @return int
     */
    public function deleteAll(string $name, array $condition) :int;

    /**
     * SQL文による更新・削除
     *
     * @param string $query
     * @return int
     */
    public function updateQuery(string $query) :int;


    public function getColumns(string $tableName): array;

    public function getTables() :array;
}