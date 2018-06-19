<?php namespace Xethron\MigrationsGenerator\Generators;

use Illuminate\Support\Facades\DB;

class SchemaGenerator {

	/**
	 * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
	 */
	protected $schema;

	/**
	 * @var FieldGenerator
	 */
	protected $fieldGenerator;

	/**
	 * @var ForeignKeyGenerator
	 */
	protected $foreignKeyGenerator;

	/**
	 * @var string
	 */
	protected $database;
	/**
	 * @var bool
	 */
	private $ignoreIndexNames;
	/**
	 * @var bool
	 */
	private $ignoreForeignKeyNames;

	/**
	 * @var string
	 */
	private $table_prefix;

	/**
	 * @param string $database
	 * @param bool   $ignoreIndexNames
	 * @param bool   $ignoreForeignKeyNames
	 */
	public function __construct($database, $ignoreIndexNames, $ignoreForeignKeyNames)
	{
		$connection = DB::connection($database)->getDoctrineConnection();
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'text');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('jsonb', 'text');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('bit', 'boolean');
		
		// Postgres types
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('_text', 'text');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('_int4', 'integer');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('_numeric', 'float');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('cidr', 'string');
		$connection->getDatabasePlatform()->registerDoctrineTypeMapping('inet', 'string');

		$this->database = $connection->getDatabase();

		$this->schema = $connection->getSchemaManager();
		$this->fieldGenerator = new FieldGenerator();
		$this->foreignKeyGenerator = new ForeignKeyGenerator();

		$this->ignoreIndexNames = $ignoreIndexNames;
		$this->ignoreForeignKeyNames = $ignoreForeignKeyNames;
		$this->table_prefix = \DB::getTablePrefix();
	}

	/**
	 * Return engine of a given table
	 * @param string $table
	 * @return string
	 */
	public function getTableEngine($table) 
	{
		$table_prefix = DB::getTablePrefix();
		DB::setTablePrefix('');
		$result = DB::table('information_schema.TABLES')
			->where('table_schema', $this->database)
			->where('table_name', $table_prefix . $table)
			->first(['engine'])->engine;
		DB::setTablePrefix($table_prefix);
		return $result;
	}

	/**
	 * @return mixed
	 */
	public function getTables()
	{
		$listTableName = $this->schema->listTableNames();
		if($this->table_prefix) {
			// remove all table_prefix from table names
			foreach ($listTableName as $key => $value) {
				$listTableName[$key] = preg_replace("/^$this->table_prefix/i", "", $value);
			}
		}
		return $listTableName;
	}

	public function getFields($table)
	{
		$table = $this->table_prefix . $table;
		return $this->fieldGenerator->generate($table, $this->schema, $this->database, $this->ignoreIndexNames);
	}

	public function getForeignKeyConstraints($table)
	{
		$table = $this->table_prefix . $table;
		return $this->foreignKeyGenerator->generate($table, $this->schema, $this->ignoreForeignKeyNames);
	}

}
