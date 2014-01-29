<?php
/**
 * Class DbCrontab.php
 * @author leo
 * @date 13.11.13
 */

class DbCrontab extends PHPDocCrontab {

	/**
	 * Running actions associated with {@link PHPDocCrontab} runner and matched with timestamp.
	 *
	 * @param array $args List of run-tags to running actions (if empty, only "default" run-tag will be runned).
	 * @throws CException when incorrect $this->timestamp
	 */
	public function actionRun($args = array()){
		$tags = &$args;
		$tags[] = 'default';

		//Getting timestamp will be used as current
		$time = strtotime($this->timestamp);
		if ($time === false) throw new CException('Bad timestamp format');
		$now = explode(' ', date('i G j n w', $time));
		$runned = 0;
		foreach ($this->prepareActions() as $task) {
			//используется разделение команд на группы по тегам
			//if (array_intersect($tags, $task['docs']['tags'])){
				foreach ($now as $key => $piece){
					//Checking current datetime on timestamp piece array.
					if (!in_array($piece, $task['docs'][0][$key])) continue 2;
				}

				//Forming command to run
				$command = $this->bootstrapScript.' '.$task['command'].' '.$task['action'];
				if (isset($task['docs']['args'])) $command .= ' '.escapeshellcmd($task['docs']['args']);

				//Setting default stdout & stderr
				if (isset($task['docs']['stdout'])) $stdout = $task['docs']['stdout'];
				else                                $stdout = $this->logFileName;

				$stdout = $this->formatFileName($stdout, $task);
				$stderr = isset($task['docs']['stderr'])?$this->formatFileName($task['docs']['stderr'], $task):$stdout;

				$this->runCommandBackground($command, $stdout, $stderr);

				Yii::log('Running task ['.(++$runned).']: '.$task['command'].' '.$task['action'], CLogger::LEVEL_INFO, 'ext.'.__CLASS__);

			//Обновляем статус для однократных заданий, чтобы задание не выполялось снова
			if($task['_model'] && $task['_model']->status === Cron::STATUS_ONCE){
				$task['_model']->setAttributes([
                    'status' => Cron::STATUS_COMPLETED,
                    'last_execution' => time()
                ]);
				$task['_model']->save(false, ['status', 'last_execution']);
			} else {
                $task['_model']->setAttributes([
                    'last_execution' => time()
                ]);
                $task['_model']->save(false, ['last_execution']);
            }
			//}
		}
		if ($runned > 0){
			Yii::log('Runned '.$runned.' task(s) at '.date('r', $time), CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
		}
		else{
			Yii::log('No task on '.date('r', $time), CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
		}
	}

	/**
	 * Getting tasklist.
	 *
	 * @return array List of command actions associated with {@link DbCrontab} runner.
	 */
	protected function prepareActions(){
		$actions = array();
		$commands = array_keys($this->getCommandRunner()->commands);
		// Jobs loop
		$record = new Cron();
		$criteria = $record->getDbCriteria()->addInCondition('status', [ Cron::STATUS_ACTIVE, Cron::STATUS_ONCE ]);
		$models = $record->findAll($criteria);
		foreach ($models as $task){
			$argsRaw = preg_split('#\s+#', $task->command);
			$command = $argsRaw[0];
			array_shift($argsRaw);
			$raw = preg_split('#\s+#', $task->interval, 5);
			if(in_array($command, $commands)){
				$args = $this->resolveRequest( $argsRaw );
                $action = $args[0];
				$actions[] = array(
					'_model' => $task,
					'command' => $command,
					'action' => $action,
					'docs' => array(
						0 => $this->transformDatePieces($raw),
						'_raw' => $raw,
                        'stdout' => Cron::outputsReplacements()[$task->stdout],
						'tags' => empty($args[2]) ? array('default') : $args[2],
						'args' => preg_replace("/($command)\s+($action)\s*/", '', $task->command) //implode(' ', $params)//остальные опции в $args[2]
					)
				);
			} else {
				Yii::log('Scheduled command "'.$command.'" ('.$task->interval.'), not found!', CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
			}
		}
		//Yii::log("Конфиг: \n" . CVarDumper::dumpAsString($actions, 4), CLogger::LEVEL_ERROR);
		return $actions;
	}

	/**
	 * В оригинальном методе исп. немного нестандартный синтаксис - вместо / исп. \ , например *\2 и тп.
	 * Transform string datetime expressions to array sets
	 *
	 * @param array $parameters
	 * @return array
	 */
	protected function transformDatePieces(array $parameters){
		$dimensions = array(
			array(0,59), //Minutes
			array(0,23), //Hours
			array(1,31), //Days
			array(1,12), //Months
			array(0,6),  //Weekdays
		);
		foreach ($parameters AS $n => &$repeat) {
			list($repeat, $every) = explode('/', $repeat, 2) + array(false, 1);
			if ($repeat === '*') $repeat = range($dimensions[$n][0], $dimensions[$n][1]);
			else {
				$repeatPiece = array();
				foreach (explode(',', $repeat) as $piece) {
					$piece = explode('-', $piece, 2);
					if (count($piece) === 2) $repeatPiece = array_merge($repeatPiece, range($piece[0], $piece[1]));
					else                     $repeatPiece[] = $piece[0];
				}
				$repeat = $repeatPiece;
			}
			if ($every > 1) foreach ($repeat AS $key => $piece){
				if ($piece%$every !== 0) unset($repeat[$key]);
			}
		}
		return $parameters;
	}
} 
