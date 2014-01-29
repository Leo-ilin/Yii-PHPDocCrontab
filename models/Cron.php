<?php
/**
 * Class Cron
 * @property int $id pk
 * @property string $command комманда: комманда экшн --параметры=знач
 * @property string $interval расписание как в кроне
 * @property string $status
 * @property string $stdout
 * @property string $comment комментарий
 * @property int $last_execution последний запуск
 */
class Cron extends CActiveRecord {

	const STATUS_ACTIVE = 'active';
	const STATUS_ONCE = 'once';
	const STATUS_NOT_ACTIVE = 'disabled';
	const STATUS_COMPLETED = 'completed';

	const OUT_FILE = 'file';
	const OUT_STDOUT = 'stdout';
	const OUT_ERR = 'stderr';

	public static function statuses(){
		return array(
			self::STATUS_ACTIVE     => 'активно',
			self::STATUS_ONCE       => 'запланировано однократно',
			self::STATUS_NOT_ACTIVE => 'отключено',
			self::STATUS_COMPLETED  => 'завершено'
		);
	}

	public static function outputs(){
		return array(
			self::OUT_FILE => 'в файл',
			self::OUT_STDOUT => 'в STDOUT',
			self::OUT_ERR => 'в STDERR'
		);
	}

	public static function outputsReplacements(){
		return array(
			self::OUT_FILE => null,
			self::OUT_STDOUT => '/dev/stdout',
			self::OUT_ERR => '/dev/stderr'
		);
	}

	public function rules(){
		return array(
			[ 'command', 'required' ],
			//регулярка не проверяет корректность расписания
			[ 'interval', 'match', 'pattern'=>'#([*0-9\/,-]+)+#', 'allowEmpty'=>false ],
			[ 'status', 'in', 'range'=>array_keys(self::statuses()) ],
			[ 'status', 'default', 'value'=>self::STATUS_ACTIVE ],
			[ 'comment', 'length', 'max'=>255],
			[ 'last_execution', 'numerical' ],
			[ 'stdout', 'length', 'max'=>15 ],
			[ 'stdout', 'default', 'value'=>self::OUT_FILE ]
		);
	}

	public function attributeLabels(){
		return array(
			'interval'=> 'Расписание',
			'command' => 'Комманда',
			'stdout'  => 'Вывод результата',
			'status'  => 'Статус задания',
			'comment' => 'Комментарий',
			'last_execution' => 'Последний запуск'
		);
	}

	public function tableName(){
		return '{{cron_jobs}}';
	}
} 