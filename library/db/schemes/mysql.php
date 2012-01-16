<?php

/**
 * MySQL-core database scheme
 */

/**#@+
 * @ignore
 */

class mysql_scheme extends sql_scheme
{
  protected $random = 'RAND()';

  protected $types = array(
              'VARCHAR' => 'string',
              'LONGTEXT' => 'string',
              'TINYTEXT' => 'string',
              'INT' => 'integer',
              'TINYINT' => 'integer',
              'SMALLINT' => 'integer',
              'MEDIUM' => 'integer',
              'BIGINT' => 'integer',
              'NUMERIC' => 'numeric',
              'DECIMAL' => 'numeric',
              'YEAR' => 'numeric',
              'DOUBLE' => 'float',
              'BOOL' => 'boolean',
              'BINARY' => 'binary',
              'VARBINARY' => 'binary',
              'LONGBLOB' => 'binary',
              'MEDIUMBLOB' => 'binary',
              'TINYBLOB' => 'binary',
              'BLOB' => 'binary',
            );

  protected $raw = array(
              'primary_key' => 'INT(11) DEFAULT NULL auto_increment PRIMARY KEY',
              'string' => array('type' => 'VARCHAR', 'length' => 255),
              'integer' => array('type' => 'INT', 'length' => 11),
              'timestamp' => array('type' => 'DATETIME'),
              'numeric' => array('type' => 'VARCHAR', 'length' => 16),
              'boolean' => array('type' => 'TINYINT', 'length' => 1),
              'binary' => array('type' => 'BLOB'),
            );

  final protected function begin_transaction() {
    return $this->execute('BEGIN TRANSACTION');
  }

  final protected function commit_transaction() {
    return $this->execute('COMMIT TRANSACTION');
  }

  final protected function rollback_transaction() {
    return $this->execute('ROLLBACK TRANSACTION');
  }

  final protected function set_encoding() {
    return $this->execute("SET NAMES 'UTF-8'");
  }

  final protected function fetch_tables() {
    $out = array();
    $old = $this->execute('SHOW TABLES');

    while ($row = $this->fetch_assoc($old)) {
      $out []= array_pop($row);
    }

    return $out;
  }

  final protected function fetch_columns($test) {
    $out = array();
    $old = $this->execute("DESCRIBE `$test`");

    while ($row = $this->fetch_assoc($old)) {
      preg_match('/^(\w+)(?:\((\d+)\))?.*?$/', strtoupper($row['Type']), $match);

      $out[$row['Field']] = array(
          'type' => $row['Extra'] == 'auto_increment' ? 'PRIMARY_KEY' : $match[1],
          'length' => ! empty($match[2]) ? (int) $match[2] : 0,
          'default' =>  $row['Default'],
          'not_null' => $row['Null'] <> 'YES',
      );
    }

    return $out;
  }

  final protected function fetch_indexes($test) {
    $out = array();

    $res = $this->execute("SHOW INDEXES FROM `$test`");

    while ($one = $this->fetch_object($res)) {
      if ($one->Key_name <> 'PRIMARY') {
        if ( ! isset($out[$one->Key_name])) {
          $out[$one->Key_name] = array(
            'unique' => ! $one->Non_unique,
            'column' => array(),
          );
        }

        $out[$one->Key_name]['column'] []= $one->Column_name;
      }
    }

    return $out;
  }

  final protected function ensure_limit($from, $to) {
    return "\nLIMIT {$from}" . ( ! empty($to) ? ",$to\n" : "\n");
  }

  final protected function rename_table($from, $to) {
    return $this->execute(sprintf('RENAME TABLE `%s` TO `%s`', $from, $to));
  }

  final protected function add_column($to, $name, $type) {
    return $this->execute(sprintf('ALTER TABLE `%s` ADD `%s` %s', $to, $name, $this->a_field($type)));
  }

  final protected function remove_column($from, $name) {
    return $this->execute(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $from, $name));
  }

  final protected function rename_column($from, $name, $to) {
    static $map = array(
              '/^VARCHAR$/' => 'VARCHAR(255)',
              '/^INT(?:EGER)$/' => 'INT(11)',
            );


    $set  = $this->columns($from);
    $test = $this->a_field($set[$name]['type'], $set[$name]['length']);
    $type = substr($test, 0, strpos($test, ' '));

    foreach ($map as $key => $val) {
      $type = preg_replace($key, $val, $type);
    }

    return $this->execute(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` %s', $from, $name, $to, $type));
  }

  final protected function change_column($from, $name, $to) {
    return $this->execute(sprintf('ALTER TABLE `%s` MODIFY `%s` %s', $from, $name, $this->a_field($to)));
  }

  final protected function add_index($to, $name, $column, $unique = FALSE) {
    $query  = sprintf('CREATE%sINDEX `%s` ON `%s` (`%s`)', $unique ? ' UNIQUE ' : ' ', $name, $to, join('`, `', $column));
    return $this->execute($query);
  }

  final protected function remove_index($name, $table) {
    return $this->execute(sprintf('DROP INDEX `%s` ON `%s`', $name, $table));
  }

  final protected function quote_string($test) {
    return "`$test`";
  }
}

/**#@-*/

/* EOF: ./library/db/schemes/mysql.php */
