#Action Render DataStore 

Новая модель работы Rest DataStore основана на принципе MVC. По этому работа PipeLine 
разделена на 2 части
* `'apiRestAction'` - pipe Line который поднимает middleware для нужного DataStore и выполняет действие.
> Описан в [actionRender.ds.global.php](../config/autoload/old/actionRender.ds.global.php#L46)

* `'dataStoreHtmlJsonRenderer'` -  Рендерит представление ответа DataStore.
  > Описан в [actionRender.ds.global.php](../config/autoload/old/actionRender.ds.global.php#L29)
  