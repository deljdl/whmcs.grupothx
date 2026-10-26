// AngularJS - APP
// Powerby MMHospedagem
// Consulta MySQL + AngularJS - Em tempo real

var app=angular.module('mmhospedagem',['angularUtils.directives.dirPagination']);

app.controller('controllerMMHospedagem', function($scope,$http){

	$scope.currentPage = 1;
    $scope.pageSize = 10;
  	$scope.meals = [];

  	$scope.obj={'idisable':false};

    $scope.ConsultaResultadoCxEntrada = function(){

        $http.get("../modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=read_cx_entrada")

        .success(function(data){
            $scope.displayCxEntrada = data
        })

        $scope.ordenar = function(keyname){
	        $scope.sortKey = keyname;
	        $scope.reverse = !$scope.reverse;
	    };

    }


    $scope.ConsultaResultadoCxEnviadas = function(){

        $http.get("../modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=read_cx_enviadas")

        .success(function(data){
            $scope.ConsultaResultadoCxEnviadas = data
        })

        $scope.ordenar_enviadas = function(keyname){
	        $scope.sortKey = keyname;
	        $scope.reverse = !$scope.reverse;
	    };

    }

    $scope.ConsultaResultadoMensagens = function(){

        $http.get("../modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=read_mensagens")

        .success(function(data){
            $scope.ConsultaResultadoMensagens = data
        })

        $scope.ordenar_mensagens = function(keyname){
	        $scope.sortKey = keyname;
	        $scope.reverse = !$scope.reverse;
	    };

    }

    $scope.ConsultaResultadoContatos = function(){

        $http.get("../modules/addons/mmhospedagem_whatsapp/mmhospedagem_api/mmhospedagem?api_whatsapp=read_contatos")

        .success(function(data){
            $scope.ConsultaResultadoContatos = data
        })

        $scope.ordenar_mensagens = function(keyname){
            $scope.sortKey = keyname;
            $scope.reverse = !$scope.reverse;
        };

    }

});

app.run();