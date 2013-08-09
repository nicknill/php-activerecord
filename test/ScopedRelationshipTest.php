<?php
require_once(__DIR__.'/RelationshipTest.php');
class ScopedRelationshipTest extends RelationshipTest
{
	
	public function set_up($connection_name=null)
	{
		parent::set_up();
		\ActiveRecord\Config::instance()->use_scoped_relationships(true);
		$this->create_test_relationship_tables();
		$this->createRelations();
		\Logger::clear();
	}
	
	public function create_test_relationship_tables()
	{
		$this->conn->query('DROP TABLE IF EXISTS pure_test_table');
		$this->conn->query("CREATE TABLE pure_test_table(
			`id` int NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NULL DEFAULT NULL,
			`date` datetime NULL DEFAULT NULL,
			`fname` varchar(50) NULL DEFAULT NULL,
			`lname` varchar(50) NULL DEFAULT NULL,
			`second_date` datetime NOT NULL,
			`created_at` datetime NULL DEFAULT NULL,
			`updated_at` datetime NULL DEFAULT NULL,
			`juggernaut_fake_null_date` datetime NOT NULL DEFAULT 0,
			`default_12` int NULL DEFAULT 12,
			`parent_id` int NULL DEFAULT NULL,
			`boolean_int` BOOLEAN NULL DEFAULT NULL,
			`tiny_int` TINYINT NULL DEFAULT NULL,
			`nullable_boolean` BOOLEAN NULL DEFAULT 1,
			PRIMARY KEY (`id`)
		)");
		$this->conn->query('DROP TABLE IF EXISTS pure_test_sub');
		$this->conn->query("CREATE TABLE pure_test_sub(
			`id` int NOT NULL AUTO_INCREMENT,
			`pure_test_id` INT NULL DEFAULT NULL,
			`super_parent_id` INT NULL DEFAULT NULL,
			`name` VARCHAR(16) NOT NULL DEFAULT '',
			`created_at` datetime NULL DEFAULT NULL,
			`updated_at` datetime NULL DEFAULT NULL,
			PRIMARY KEY (`id`)
		)");
		
		$this->conn->query('DROP TABLE IF EXISTS pure_super_parent');
		$this->conn->query("CREATE TABLE pure_super_parent(
			`id` int NOT NULL AUTO_INCREMENT,
			`super_parent_id` INT NULL DEFAULT NULL,
			`created_at` datetime NULL DEFAULT NULL,
			`updated_at` datetime NULL DEFAULT NULL,
			PRIMARY KEY (`id`)
		)");
	}
	
	public function createRelations()
	{
		$parent = new PureTest();
		$parent->id = 1;
		$parent->second_date = 'now';
		$parent->fname = 'first';
		$parent->save();
		$sub = new PureTestSub();
		$sub->id = 2;
		$sub->pure_test_id = $parent->id;
		$sub->super_parent_id = 3;
		$sub->name = 'two';
		$sub->save();
		$sub = new PureTestSub();
		$sub->id = 3;
		$sub->pure_test_id = $parent->id;
		$sub->super_parent_id = 1;
		$sub->name = 'three';
		$sub->save();
		
		$parent = new PureTest();
		$parent->id = 16;
		$parent->second_date = 'now';
		$parent->fname = 'No Children';
		$parent->save();
		
		$sub = new PureTestSub();
		$sub->id = 4;
		$sub->pure_test_id = null;
		$this->assertTrue($sub->save());
		
		
		$parent = new PureTest();
		$parent->id = 15;
		$parent->second_date = 'now';
		$parent->fname = 'fifteenth';
		$parent->save();
		$sub = new PureTestSub();
		$sub->id=12;
		$sub->pure_test_id = $parent->id;
		$sub->super_parent_id = 2;
		$sub->save();
		
		$super = new PureSuperParent();
		$super->id = 1;
		$super->save();
		$super = new PureSuperParent();
		$super->id = 2;
		$super->save();
		$super = new PureSuperParent();
		$super->id = 3;
		$super->save();
		
		$parent = new PureTest();
		$parent->id = 18;
		$parent->second_date = 'now';
		$parent->fname = 'eighteen';
		$parent->save();
		
		$sub = new PureTestSub();
		$sub->id=18;
		$sub->pure_test_id = $parent->id;
		$sub->super_parent_id = null;
		$sub->save();
		
		$previousParentId = $parent->id;
	}
	public function test_loading_parent_belongs_to_relationship()
	{
		$parent = PureTest::find(15);
		$sub = PureTestSub::find(12);
		$this->assertEquals($parent->id,
			$sub->pure_test->id);
		$this->assertEquals('fifteenth',$sub->pure_test->fname);
	}
	
	public function test_loading_has_one_when_non_exist_returns_false()
	{
		$parent = PureTest::find(16);
		$this->assertNull($parent->sub);
	}
	
	public function test_loading_a_subselect_of_a_relationship()
	{
		$parent = PureTest::find(15);
		$sub = PureTestSub::find(12);
		$this->assertEquals($parent->id,
			$sub->pure_test->select('id')->id);
		$this->assertNull($sub->pure_test->fname);
	}
	
	public function test_loading_has_many_relationship_returns_relationship_object()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs;
		$this->assertInstanceOf('JDO\Relationship',$subs);
	}
	
	public function test_can_specify_to_retrieve_models_instead_of_relationship_object()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs_raw;
		$this->assertTrue(is_array($subs));
		$this->assertInstanceOf('PureTestSub',$subs[0]);
	}
	public function test_can_specify_to_retrieve_models_instead_of_relationship_object_on_has_one()
	{
		$parent = PureTest::find(1);
		$subs = $parent->sub_raw;
		$this->assertInstanceOf('PureTestSub',$subs);
	}
	
	public function test_has_many_without_select()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs->all(array('order'=>'id ASC'));
		$this->assertEquals('two',$subs[0]->name);
	}
	
	public function test_has_many_with_select()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs->select('id')->all();
		$this->assertEquals('',$subs[0]->name);
	}
	
	public function test_count_of_relationship_is_correct()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs;
		$this->assertEquals(2,count($subs));
	}
	
	public function test_has_one()
	{
		$parent = PureTest::find(1);
		$sub = $parent->sub;
		$this->assertEquals($parent->id,$sub->pure_test->id);
	}
	
	public function test_retrieving_relations_by_index()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs;
		$this->assertEquals(3,$subs[0]->id);
		$this->assertEquals(2,$subs[1]->id);
	}
	
	public function testAsArrayReturnsRelationshipAsAnArray()
	{
		$parent = PureTest::find(1);
		$subs = $parent->subs;
		$arraySubs = $subs->asArray();
		foreach($subs as $index=>$sub)
		{
			$this->assertEquals($arraySubs[$index],$sub);
		}
	}
	
	public function test_loaded_relations_are_correct()
	{
		$parent = PureTest::find(1);
		$looped = false;
		$looped1 = false;
		$looped2 = false;
		foreach($parent->subs as $index=>$sub)
		{
			$this->assertTrue(is_int($index));
			$looped = true;
			//They're loaded in reverse order in the relationship definition
			if($index === 0)
			{
				$looped1 = true;
				$this->assertEquals(3,$sub->id);
			}
			if($index === 1)
			{
				$looped2 = true;
				$this->assertEquals(2,$sub->id);
			}
		}
		$this->assertTrue($looped);
		$this->assertTrue($looped1);
		$this->assertTrue($looped2);
	}
	
	public function test_eager_loading_has_many()
	{
		$criteria['include']=array('subs');
		$criteria['conditions'] = array('id IN (1,15)');
		$criteria['order'] = 'id ASC';
		$parents = PureTest::all($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());

		$this->assertEquals(2,count($parents[0]->subs));
		$this->assertEquals(3,$parents[0]->subs[0]->id);
		$this->assertEquals(2,$parents[0]->subs[1]->id);
		
		$this->assertEquals(1,count($parents[1]->subs));
		$this->assertEquals(12,$parents[1]->subs[0]->id);
		
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()));
	}
	
	public function test_eager_loading_has_one()
	{
		$criteria['include']=array('sub');
		$criteria['conditions'] = array('id IN (1,15)');
		$criteria['order'] = 'id ASC';
		$parents = PureTest::all($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());

		$this->assertEquals(3,$parents[0]->sub->id);
		$this->assertEquals(12,$parents[1]->sub->id);
		
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()));
	}

	public function test_eager_loading_has_one_with_a_null_involved()
	{
		$criteria['include']=array('sub');
		$criteria['conditions'] = array('id IN (16)');
		$criteria['order'] = 'id ASC';
		$parents = PureTest::all($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		$this->assertNull($parents[0]->sub);
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()));
	}
	
	
	
	public function test_eager_loading_belongs_to_one()
	{
		$criteria['include']=array('pure_test');
		$criteria['conditions'] = array('pure_test_id IS NOT NULL');
		$subs = PureTestSub::all($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());

		
		$this->assertEquals(1,$subs[0]->pure_test->id);
		$this->assertEquals(1,$subs[1]->pure_test->id);
		$this->assertEquals(15,$subs[2]->pure_test->id);
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()));
	}
	
	public function test_eager_loading_has_many_back_to_has_one()
	{
		$criteria['include']=array('subs'=>array('pure_test'));
		$criteria['conditions'] = array('id IN (1,15)');
		$criteria['order'] = 'id ASC';
		$parents = PureTest::all($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());

		$this->assertEquals(2,count($parents[0]->subs));
		$this->assertEquals(3,$parents[0]->subs[0]->id);
		$this->assertEquals(2,$parents[0]->subs[1]->id);
		
		$this->assertEquals(1,count($parents[1]->subs));
		$this->assertEquals(12,$parents[1]->subs[0]->id);
		
		$this->assertEquals(1,$parents[0]->subs[0]->pure_test->id);
		$this->assertEquals(1,$parents[0]->subs[1]->pure_test->id);
		$this->assertEquals(15,$parents[1]->subs[0]->pure_test->id);
		
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()));
		
		$this->assertSame($parents[0],$parents[0]->subs[0]->pure_test->lazyLoadRelationship());
		
		//Test circular relationship
		$this->assertSame($parents[0]->subs[0]->pure_test,$parents[0]->subs[0]->pure_test->subs[0]->pure_test);
	}
	
	public function test_eager_loading_select_clause()
	{
		$criteria['include']=array('select_subs');
		$criteria['order'] = 'id ASC';
		$criteria['limit'] = 1;
		$parents = PureTest::all($criteria);
		
		$firstSub = $parents[0]->select_subs[0];
		
		$this->assertNotNull($firstSub->created_at);
		$this->assertNull($firstSub->updated_at);
	}
	
	public function test_getting_a_subset_of_a_relationship()
	{
		$parent = PureTest::first();
		$queriesPerformed = count(Mysql::loggedQueries());
		$subs = $parent->subs;
		
		//A query to find out if there are results will be performed.
		$this->assertEquals($queriesPerformed+1,count(Mysql::loggedQueries()));

		$this->assertEquals(2,$parent->subs->first(array('conditions'=>array('id'=>2)))->id,Mysql::getLastQuery());
	}
	
	public function test_getting_a_subset_of_a_relationship_that_does_not_exist()
	{
		$parent = PureTest::first();
		$this->assertNull($parent->subs->first(array('conditions'=>array('id'=>1))),Mysql::getLastQuery());
	}
	
	public function test_getting_a_subset_of_a_relationship_twice()
	{
		$parent = PureTest::first();
		$this->assertEquals(2,$parent->subs->first(array('conditions'=>array('id'=>2)))->id,Mysql::getLastQuery());
		
		$secondSub = $parent->subs->first(array('conditions'=>array('id'=>3)));
		$this->assertNotNull($secondSub,Mysql::getLastQuery());
		$this->assertEquals(3,$secondSub->id);
	}
	
	public function test_getting_super_parents()
	{
		$sub = PureTestSub::find(2);
		$this->assertEquals(3,$sub->super_parent->id);
	}
	
	public function test_has_many_through()
	{
		$parent = PureTest::first(1);
		
		$super_parents = $parent->super_parents;
		$this->assertEquals(2,count($super_parents));
		$this->assertEquals(1,$super_parents[0]->id);
		$this->assertEquals(3,$super_parents[1]->id);
	}
	
	public function test_getting_subset_of_has_many_through()
	{
		$parent = PureTest::first(1);
		
		$super_parent = $parent->super_parents->first(array('conditions'=>array('id'=>1)));
		$this->assertEquals(1,$super_parent->id);
		$this->assertEquals(2,count($parent->super_parents));
	}
	
	public function test_has_one_through()
	{
		$parent = PureTest::first(1);
		
		$super_parent = $parent->super_parent;
		$this->assertEquals(1,$super_parent->id,print_r(Mysql::loggedQueries(),true));
	}
	
	public function test_eager_loading_has_many_through_with_one_result()
	{
		$criteria['include']=array('super_parents');
		$criteria['conditions']=array('id'=>1);
		$parent = PureTest::first($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		
		$super_parents = $parent->super_parents;
		$this->assertEquals(2,count($super_parents));
		$this->assertEquals(1,$super_parents[0]->id);
		$this->assertEquals(3,$super_parents[1]->id);
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()),
			'More queries were performed instead of an eager load taking place');
	}
	
	public function test_eager_loading_has_many_through_with_one_result_loads_betweener_relationship()
	{
		$criteria['include']=array('super_parents');
		$criteria['conditions']=array('id'=>1);
		$parent = PureTest::first($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		
		$this->assertEquals(3,$parent->subs[0]->id);
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()),
			'More queries were performed instead of an eager load taking place');
	}
	
	public function test_eager_loading_has_many_through_with_null_betweener()
	{
		$criteria['include']=array('super_parents');
		$criteria['conditions']=array('id'=>16);
		$parent = PureTest::first($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		
		$super_parents = $parent->super_parents;
		$this->assertEquals(0,count($super_parents));
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()),
			'More queries were performed instead of an eager load taking place');
	}
	
	public function test_eager_loading_has_many_through_with_null_final()
	{
		$criteria['include']=array('super_parents');
		$criteria['conditions']=array('id'=>18);
		$parent = PureTest::first($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		
		$super_parents = $parent->super_parents;
		$this->assertEquals(0,count($super_parents));
		$this->assertEquals($queriesPerformed,count(Mysql::loggedQueries()),
			'More queries were performed instead of an eager load taking place');
	}
	
	public function test_eager_loading_back_through_has_many_from_null_final()
	{
		$criteria['include']=array('super_parents');
		$criteria['conditions']=array('id'=>18);
		$parent = PureTest::first($criteria);
		$queriesPerformed = count(Mysql::loggedQueries());
		
		$super_parents = $parent->super_parents;
		if(!is_array($parent->super_parents) || $parent->super_parents)
		{
			$this->fail('Expected an empty array as opposed to the relationship');
		}
		else
		{
			return true;
		}
	}
	
	public function test_has_many_build_association()
	{
		return true;
	}
	
	public function test_build_association_overwrites_guarded_foreign_keys()
	{
		return true;
	}
	
	public function test_has_many_with_sql_clause_options()
	{
		return true;
		Venue::$has_many[0] = array('events',
			'select' => 'type',
			'group'  => 'type',
			'limit'  => 2,
			'offset' => 1);
		Venue::first()->events;
		$this->assert_sql_has($this->conn->limit("SELECT type FROM events WHERE venue_id=? GROUP BY type",1,2),Event::table()->last_sql);
	}
	
	public function test_gh93_and_gh100_eager_loading_respects_association_options()
	{
		Venue::$has_many = array(array('events', 'class_name' => 'Event', 'order' => 'id asc', 'conditions' => array('length(title) = ?', 14)));
		$venues = Venue::find(array(2, 6), array('include' => 'events'));

		$this->assert_equals(1, count($venues[0]->events));
    }
	
	public function test_eager_loading_clones_related_objects()
	{
		//Actually DO want them to not be cloned
		$events = Event::find(array(2,3), array('include' => array('venue')));

		$venue = $events[0]->venue;
		$venue->name = "new name";

		$this->assert_equals($venue->id, $events[1]->venue->id);
		$this->assert_equals($venue->name, $events[1]->venue->name);
		$this->assert_equals(spl_object_hash($venue), spl_object_hash($events[1]->venue));
	}

	public function test_eager_loading_clones_nested_related_objects()
	{
		//Actually DO want them to not be cloned
		$venues = Venue::find(array(1,2,6,9), array('include' => array('events' => array('host'))));

		$unchanged_host = $venues[2]->events[0]->host;
		$changed_host = $venues[3]->events[0]->host;
		$changed_host->name = "changed";

		$this->assert_equals($changed_host->id, $unchanged_host->id);
		$this->assert_equals($changed_host->name, $unchanged_host->name);
		$this->assert_equals(spl_object_hash($changed_host), spl_object_hash($unchanged_host));
	}
	
}


class PureTest extends \ActiveRecord\Model
{
	public static $table_name = 'pure_test_table';
	public static $attr_accessible = array('name','fname');
	
	protected $setterSetter;
	public function setSetterSetter($rawr)
	{
		$this->setterSetter = 'Rawr';
	}
	
	protected static function instantiate($data)
	{
		if(isset($data['fname']) )
		{
			if($data['fname'] == 'dynamic-instantiation')
				return new PureTestDynamicInstantiation();
			if($data['fname'] == 'dynamic-instantiation-fail')
				return new PureTestDynamicNoInherit();
		}
		return parent::instantiate($data);
	}
	
	public function getSetterSetter()
	{
		return $this->setterSetter;
	}
	public static function hasMany()
	{
		$relations['subs'] = array('foreignKey'=>'pure_test_id','className'=>'PureTestSub','order'=>'id DESC','asModels'=>false);
		$relations['subs_raw'] = array('foreignKey'=>'pure_test_id','className'=>'PureTestSub','order'=>'id DESC','asModels'=>true);
		$relations['select_subs'] = $relations['subs'];
		$relations['select_subs']['select'] = 'id, pure_test_id, created_at';
		
		$relations['super_parents'] = array('through'=>'subs','to'=>'super_parent','asModels'=>false);
		return $relations;
	}
	
	public static function hasOne()
	{
		$relations['sub'] = array('foreignKey'=>'pure_test_id','className'=>'PureTestSub','order'=>'id DESC','asModels'=>false);
		$relations['sub_raw'] = array('foreignKey'=>'pure_test_id','className'=>'PureTestSub','order'=>'id DESC','asModels'=>true);
		$relations['super_parent'] = array('through'=>'sub','to'=>'super_parent','asModels'=>false);
		
		return $relations;
	}
	
}

class PureTestSub extends PureTest
{
	public static $table_name = 'pure_test_sub';
	
	public static function belongsTo()
	{
		$array['pure_test']=array('className'=>'PureTest','foreignKey'=>'pure_test_id','asModels'=>false);
		
		$array['super_parent']=array('className'=>'PureSuperParent','foreignKey'=>'super_parent_id','asModels'=>false);
		return $array;
	}
	
}

class PureSuperParent extends \ActiveRecord\Model
{
	public static $table_name = 'pure_super_parent';
	
	public static function hasMany()
	{
		$array['pure_tests']=array('className'=>'PureTest','through'=>'pure_test_subs');
		$array['pure_test_subs']=array('className'=>'PureTestSub','foreignKey'=>'super_parent_id');
		return $array;
	}
}

