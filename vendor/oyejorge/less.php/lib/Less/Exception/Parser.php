<?php

/**
 * Parser Exception
 *
 * @package Less
 * @subpackage exception
 */
class Less_Exception_Parser extends Exception{

	/**
	 * The current file
	 *
	 * @var Less_ImportedFile
	 */
	public $currentFile;

	/**
	 * The current parser index
	 *
	 * @var integer
	 */
	public $index;

	protected $input;

	protected $details = array();


	/**
	 * Constructor
	 *
	 * @param string $message
	 * @param Exception $previous Previous exception
	 * @param integer $index The current parser index
	 * @param Less_FileInfo|string $currentFile The file
	 * @param integer $code The exception code
	 */
	public function __construct($message = null, Exception $previous = null, $index = null, $currentFile = null, $code = 0){

		if (PHP_VERSION_ID < 50300) {
			$this->previous = $previous;
			parent::__construct($message, $code);
		} else {
			parent::__construct($message, $code, $previous);
		}

		$this->currentFile = $currentFile;
		$this->index = $index;

		$this->genMessage();
	}


	protected function getInput(){

		if( !$this->input && $this->currentFile && $this->currentFile['filename'] && file_exists($this->currentFile['filename']) ){
			$this->input = file_get_contents( $this->currentFile['filename'] );
		}
	}



	/**
	 * Converts the exception to string
	 *
	 * @return string
	 */
	public function genMessage(){

		if( $this->currentFile && $this->currentFile['filename'] ){
			$this->message .= ' in '.basename($this->currentFile['filename']);
		}

		if( $this->index !== null ){
			$this->getInput();
			if( $this->input ){
				$line = self::getLineNumber();
				$this->message .= ' on line '.$line.', column '.self::getColumn();

				$lines = explode("\n",$this->input);

				$count = count($lines);
				$start_line = max(0, $line-3);
				$last_line = min($count, $start_line+6);
				$num_len = strlen($last_line);
				for( $i = $start_line; $i < $last_line; $i++ ){
					$this->message .= "\n".str_pad($i+1,$num_len,'0',STR_PAD_LEFT).'| '.$lines[$i];
				}
			}
		}

	}

	/**
	 * Returns the line number the error was encountered
	 *
	 * @return integer
	 */
	public function getLineNumber(){
		if( $this->index ){
			// https://bugs.php.net/bug.php?id=49790
			if (ini_get("mbstring.func_overload")) {
				return substr_count(substr($this->input, 0, $this->index), "\n") + 1;
			} else {
				return substr_count($this->input, "\n", 0, $this->index) + 1;
			}
		}
		return 1;
	}


	/**
	 * Returns the column the error was encountered
	 *
	 * @return integer
	 */
	public function getColumn(){

		$part = substr($this->input, 0, $this->index);
		$pos = strrpos($part,"\n");
		return $this->index - $pos;
	}

}
