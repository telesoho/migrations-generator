<?php namespace Xethron\MigrationsGenerator\Syntax;

/**
 * Class AddForeignKeysToTable
 * @package Xethron\MigrationsGenerator\Syntax
 */
class AddForeignKeysToTable extends Table {

	/**
	 * Return string for adding a foreign key
	 *
	 * @param array $foreignKey
	 * @return string
	 */
	protected function getItem(array $foreignKey)
	{
		$table_prefix = \DB::getTablePrefix();
		$noprefixForeignKeyOn = preg_replace("/^${table_prefix}/i", "", $foreignKey['on']);
		$noprefixForeignKeyName = preg_replace("/^${table_prefix}/i", "", $foreignKey['name']);

		$value = $foreignKey['field'];
		if ( ! empty($noprefixForeignKeyName)) {
			$value .= "', '". $noprefixForeignKeyName;
		}
		$output = sprintf(
			"\$table->foreign('%s')->references('%s')->on('%s')",
			$value,
			$foreignKey['references'],
			$noprefixForeignKeyOn
		);
		if ($foreignKey['onUpdate']) {
			$output .= sprintf("->onUpdate('%s')", $foreignKey['onUpdate']);
		}
		if ($foreignKey['onDelete']) {
			$output .= sprintf("->onDelete('%s')", $foreignKey['onDelete']);
		}
		if (isset($foreignKey['decorators'])) {
			$output .= $this->addDecorators($foreignKey['decorators']);
		}
		return $output . ';';
	}

}
