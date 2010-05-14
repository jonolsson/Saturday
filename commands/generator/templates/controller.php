<?php
class controllers_namespace_nameplural extends api_controller {

    function indexAction() {
        $namepluralMapper = new mappers_nameplural();
        $nameplural = $namepluralMapper->findAll();
        $this->view->nameplural = $nameplural;
    }

    function showAction() {
        $namepluralMapper = new mappers_nameplural();
        $name = $namepluralMapper->find($this->params->id);
        $this->view->name = $name;
    }

    function newAction() {
        $namepluralMapper = new mappers_nameplural();
        $name = new models_name();
        if ($this->request->isPost()) {
            $params = $this->request->getParams();
            $name = new models_name($params);
            if ($namepluralMapper->insert($name)) {
                $this->redirectTo('/namespace/nameplural');
            }
        }
        $this->view->name = $name;
    }

    function editAction() {
        $namepluralMapper = new mappers_nameplural();
        if ($this->request->isPost()) {
            $params = $this->request->getParams();
            $name = new models_name($params);
            if ($namepluralMapper->update($name)) {
                $this->redirectTo('/namespace/nameplural');
            }
        } else {
            $name = $namepluralMapper->find($this->params->id);
        }
        $this->view->name = $name;
    }

    function destroyAction() {
        $namepluralMapper = new mappers_nameplural();
        $namepluralMapper->delete($this->params->id);
        $this->redirectTo('/namespace/nameplural');
    }
}


