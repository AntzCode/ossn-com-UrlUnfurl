<?php
###################################################################################
##               Open Source Social Network (Component/Extension)                ##
##          ~ Unfurl URL's for a preview when posting links on a wall ~          ##
##                                                                               ##
##    @package   UrlUnfurl Component                                             ##
##    @author    AntzCode Ltd                                                    ##
##    @copyright (C) AntzCode Ltd                                                ##
##    @link      https://github.com/AntzCode/ossn-com-UrlUnfurl                  ##
##    @license   GPLv3 https://raw.githubusercontent.com/AntzCode/               ##
##                       ossn-com-UrlUnfurl/main/LICENSE                         ##
##                                                                               ##
###################################################################################

namespace UrlUnfurl\Queue;

use UrlUnfurl\Queue\Item\DataObject;
use UrlUnfurl\Url;
use UrlUnfurl\Database;
use UrlUnfurl\HtmlDocument;
use UrlUnfurl\Database\PersistentObjectInterface;

class Item implements PersistentObjectInterface
{
	const STATUS_NEW = 'new';
	const STATUS_SCRAPED = 'scraped';

	protected $id;
	protected $status;
	protected $processId;
	protected $isQueued;
	protected $data;

	protected function __construct(){
		$this->data = new DataObject();
	}

	public function getStatus(){
		return $this->status;
	}
	public function getProcessId(){
		return $this->processId;
	}
	public function getIsQueued(){
		return $this->isQueued;
	}
	protected function getData(){
		return $this->data;
	}

	public function setHtmlDocument(HtmlDocument $htmlDocument){
		$this->setStatus(self::STATUS_SCRAPED);
		$this->data->HtmlDocument = $htmlDocument;
	}

	public function persistUnique($wheres)
	{
		Database::delete('_url_queue', $wheres, ['time_created' => 'ASC'], 10000);
		return $this->persist();
	}

	public function persist(){

		Database::update('_url_queue',
			[
				'process_id' => $this->processId,
				'status' => $this->status,
				'data' => serialize($this->data)
			],
			['id' => $this->id]
		);

	}

	public function drop(){

		if(isset($this->id)){
			return Database::delete('_url_queue', [ 'id' => $this->id ], ['id' => 'ASC'], 1);
		}

	}

	public function loadBy($wheres){

		if($row = Database::getOneBy('_url_queue', $wheres)){
			$this->id = $row->id;
			$this->setIsQueued(true);
			$this->setStatus($row->status);
			$this->setProcessId($row->processId);
			$this->setData(unserialize($row->data));

			return $this;

		}else{
			return false;
		}

	}

	protected function setStatus($status){

		if(in_array($status, [
			self::STATUS_NEW,
			self::STATUS_SCRAPED
		])){
			$this->status = $status;
		}

	}

	protected function setProcessId($processId){
		$this->processId = $processId;
	}

	protected function setIsQueued($bool){
		$this->isQueued = $bool;
	}

	protected function setData($data){
		$this->data = $data;
	}

	public function getUrl(){
		return $this->data->Url;
	}

	public function getHtmlDocument(){
		return $this->data->HtmlDocument;
	}

	public static function loadOneBy($wheres){

		$row = Database::getOneBy('_url_queue', $wheres);

		if(is_object($row)){
			$queueItem = new self();
			$queueItem->id = $row->id;
			$queueItem->setIsQueued(true);
			$queueItem->setStatus($row->status);
			$queueItem->setProcessId($row->processId);
			$queueItem->setData(unserialize($row->data));
			return $queueItem;
		}

		return null;

	}

	public static function loadAllBy($wheres, $sorting, $limits){

		$rows = Database::getBy('_url_queue', $wheres, ['time_created' => 'DESC'], [0, 10]);

		if(is_array($rows) && count($rows) > 0) {
			$queueItems = [];
			foreach($rows as $row){
				$queueItem = new self();
				$queueItem->id = $row->id;
				$queueItem->setIsQueued(true);
				$queueItem->setStatus($row->status);
				$queueItem->setProcessId($row->processId);
				$queueItem->setData(unserialize($row->data));
				$queueItems[] = $queueItem;
			}

			return $queueItems;

		}else{
			return [];
		}
	}

	public static function getOneByUrl(Url $url){
		return self::loadOneBy([ 'url' => $url->url ]);
	}

	public static function getOneByProcessId(string $processId){
		return self::loadOneBy([ 'process_id' => $processId ]);
	}

	public static function createFromUrl(Url $url){

		$uniqueId = Database::generateUniqueId();
		$newData = new \stdClass();
		$newData->Url = $url;

		if(Database::insert('_url_queue', [
			'url' => $url->rawUrl,
			'status' => self::STATUS_NEW,
			'process_id' => $uniqueId,
			'data' => serialize($newData)
		])){
			$queueItem = new self();
			$queueItem->setIsQueued(true);
			$queueItem->setStatus(self::STATUS_NEW);
			$queueItem->setProcessId($uniqueId);
			$queueItem->setData($newData);

			return $queueItem;

		}else{
			return false;
		}

	}

}