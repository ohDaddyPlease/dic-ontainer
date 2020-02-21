<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Container
{

  //хранилище сервисов по типу ключ-значение
  public $services = [];

  //хранилище отданных (готовых) сервисов (экземпляров)
  public $servicesStore = [];

  //метод для занесения сущности в хранилище
  public function set(string $abstract, $service = null)
  {
    if(!isset($service)) $service = $abstract;
    $this->services[$abstract] = $service; 
  }

  //метод получения сущности из хранилища
  public function get(string $service, array $parameters = [])
  {

    if(isset($this->servicesStore[$service]))
      return $this->servicesStore[$service];

    //аргументы 
    $args = [];

    if(!isset($this->services[$service]))
      $this->set($service);

    //--- КЛАСС ---
    //если нельзя проинстансить (создать экземпляр), выкинуть ошибку
    if(!($class = new ReflectionClass($service))->isInstantiable()) 
      throw new Exception('Ошибочка');

    //если нет конструктора, создать и вернуть инстанс без конструктора
    if(!($constructor = $class->getConstructor()))
      return $this->servicesStore[$service] = $class->newInstance();

    $params = $constructor->getParameters();
    //если есть конструктор, получить параметры и проверить (1) и (2)
    foreach($params as $param)
    {
      //(1) является ли параметр типом класса (приставкой), если является, 
      //то (1.1) 
      if($type = $param->getClass()){

        //(1.1) достать его из хранилища, в ином случае - создать и выдать
        $args[] = $this->get($type->name);

      }else
      {
          //(2) или если параметр - не тип класса, то занести в передаваемые аргументы
          array_push($args, ...$parameters);
      }

    }

    //создание экземпляра и занесение в хранилище экземпляров
    return $this->servicesStore[$service] = $class->newInstanceArgs($args);

  }

  //метод для проверки, есть ли в хранилище экземпляр
  public function has(string $service)
  {
    return isset($this->services[$service]) ? true : false;
  }

}

//test
class Request
{
  public $name = 'Класс Request, йоу';
}

class GetRequest
{
  public $name = 'Класс GetRequest, йоу';
}

class PostRequest
{
  public $request;
  public $get;
  public $param1;
  public $param2;


  public function __construct(Request $request, GetRequest $get, $param1, $param2)
  {
    $this->request = $request;
    $this->get = $get;
    $this->param1 = $param1;
    $this->param2 = $param2;

  }
}

$container = new Container;

$container->set('PostRequest');

exit(var_dump(
  $container->get('PostRequest', ['other param 1', 'other param 2'])
));