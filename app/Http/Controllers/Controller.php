<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
//////////////////////////////////////////////////////////////////////////////
use Illuminate\Http\Request;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
//////////////////////////////////////////////////////////////////////////////
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
//////////////////////////////////////////////////////////////////////////////
class ReportesController extends VoyagerBaseController
{
    public function index(Request $request)
    {
        // Obtener el valor del campo type_vehicle del bread.
        $type_vehicle = $request->query('type_vehicle');

        // Realizar una consulta a la tabla de vehículos para obtener todas las placas de los vehículos que tengan el tipo de vehículo especificado.
        $vehicles = \App\vehicles::where('type_vehicle', $type_vehicle)->get();

        // Actualizar el campo matriculas del bread con las placas obtenidas.
        $request->session()->put('breportes.placa', $vehicles->pluck('license'));

        return parent::index($request);
    }
}
//////////////////////////////////////////////////////////////////////////////