@extends('voyager::master')

@section('page_title', __('voyager::generic.' . (isset($dataTypeContent->id) ? 'edit' : 'add')) . ' ' .
    $dataType->getTranslatedAttribute('display_name_singular'))

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.' . (isset($dataTypeContent->id) ? 'edit' : 'add')) . ' ' . $dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <form class="form-edit-add" role="form"
            action="@if (!is_null($dataTypeContent->getKey())) {{ route('voyager.' . $dataType->slug . '.update', $dataTypeContent->getKey()) }}@else{{ route('voyager.' . $dataType->slug . '.store') }} @endif"
            method="POST" enctype="multipart/form-data" autocomplete="off">
            <!-- PUT Method if we are editing -->
            @if (isset($dataTypeContent->id))
                {{ method_field('PUT') }}
            @endif
            {{ csrf_field() }}

            <div class="row">
                <div class="col-md-7">
                    <div class="panel panel-bordered">
                        {{-- <div class="panel"> --}}
                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="panel-body">

                            <div class="form-group col-md-6">
                                <label for="name">{{ __('voyager::generic.name') }}</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="{{ __('voyager::generic.name') }}"
                                    value="{{ old('name', $dataTypeContent->name ?? '') }}" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">{{ __('voyager::generic.email') }}</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="{{ __('voyager::generic.email') }}"
                                    value="{{ old('email', $dataTypeContent->email ?? '') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="documentType">Tipo de documento</label>
                                <select id="documentType" name="documentType" class="form-control">
                                    <option value="{{ old('documentType', $dataTypeContent->documentType ?? '') }}">
                                        {{ old('documentType', $dataTypeContent->documentType ?? '') }}</option>
                                    <option value="C.C">Cedula de ciudadania</option>
                                    <option value="NIT">NIT</option>
                                    <option value="C.E">Cedula de extranjeria</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="document">No. documento</label>
                                <input type="text" class="form-control" id="document" name="document" placeholder=""
                                    value="{{ old('document', $dataTypeContent->document ?? '') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="Genre">Genero</label>
                                <select id="Genre" name="Genre" class="form-control">
                                    <option value="{{ old('Genre', $dataTypeContent->Genre ?? '') }}">
                                        {{ old('Genre', $dataTypeContent->Genre ?? '') }}</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="No binario">No binario</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="dateBirth">Fecha de nacimiento</label>

                                <input type="date" class="form-control" id="start" name="dateBirth"
                                    value="{{ old('dateBirth', $dataTypeContent->dateBirth ?? '') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password">Contraseña</label>
                                @if (isset($dataTypeContent->password))
                                    <small>| Si deja el campo vacio, se usara la contraseña actual</small>
                                @endif
                                <input type="password" class="form-control" id="password" name="password" value=""
                                    autocomplete="new-password">
                            </div>

                            @can('editRoles', $dataTypeContent)
                                @php
                                    $dataTypeRows = $dataType->{isset($dataTypeContent->id) ? 'editRows' : 'addRows'};
                                    $row = $dataTypeRows->where('field', 'user_belongsto_role_relationship')->first();
                                    $options = $row->details;
                                @endphp
                                @if (Auth::user()->role_id != 3)
                                   
                                @else
                                @endif
                                <div class="form-group col-md-6">
                                    <label for="default_role">{{ __('voyager::profile.role_default') }}</label>


                                    @include('voyager::formfields.relationship')


                                </div>
                            @endcan
                            @php
                                if (isset($dataTypeContent->locale)) {
                                    $selected_locale = $dataTypeContent->locale;
                                } else {
                                    $selected_locale = config('app.locale', 'en');
                                }
                                
                            @endphp
                            <div class="form-group" style="display:none">
                                <label for="locale">{{ __('voyager::generic.locale') }}</label>
                                <select class="form-control select2" id="locale" name="locale">
                                    @foreach (Voyager::getLocales() as $locale)
                                        <option value="{{ $locale }}"
                                            {{ $locale == $selected_locale ? 'selected' : '' }}>{{ $locale }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="panel panel panel-bordered panel-warning <?php if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2){}else{echo 'd-none';}?>">
                        <div class="panel-body">
                            
                            <div class="form-group">
                                <label for="additional_roles">Vehiculos asignados</label>
                                @php
                                    $dataTypeRows = $dataType->{isset($dataTypeContent->id) ? 'editRows' : 'addRows'};
                                    $row = $dataTypeRows->where('field', 'user_belongstomany_role_relationship')->first();
                                    $options = $row->details;
                                @endphp
                                @include('voyager::formfields.relationship')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel panel panel-bordered panel-warning">
                        <div class="panel-body">
                            <div class="form-group">
                                @if (isset($dataTypeContent->avatar))
                                    <img src="{{ filter_var($dataTypeContent->avatar, FILTER_VALIDATE_URL) ? $dataTypeContent->avatar : Voyager::image($dataTypeContent->avatar) }}"
                                        style="width:200px; height:auto; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:10px;" />
                                @endif
                                <input type="file" data-name="avatar" name="avatar">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel panel-bordered">
                        <div class="panel-body">
                            <div class="form-group col-md-6">
                                <label for="Address">Dirección</label>
                                <input type="text" class="form-control" id="Address" name="Address"
                                    placeholder="Dirección"
                                    value="{{ old('Address', $dataTypeContent->Address ?? '') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="Neighborhood">Barrio / Localidad</label>
                                <input type="text" class="form-control" id="Neighborhood" name="Neighborhood"
                                    placeholder="Barrio"
                                    value="{{ old('Neighborhood', $dataTypeContent->Neighborhood ?? '') }}">
                            </div>
                            <div class="form-group col-md-5">
                                <label for="phoneNumber">Telefono</label>
                                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                    placeholder="Telefono"
                                    value="{{ old('phoneNumber', $dataTypeContent->phoneNumber ?? '') }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="relationshipStatus">Estado civil</label>
                                <select id="relationshipStatus" name="relationshipStatus" class="form-control">
                                    <option
                                        value="{{ old('relationshipStatus', $dataTypeContent->relationshipStatus ?? '') }}">
                                        {{ old('relationshipStatus', $dataTypeContent->relationshipStatus ?? '') }}
                                    </option>
                                    <option value="Soltero">Soltero</option>
                                    <option value="Casado">Casado</option>
                                    <option value="Viudo">Viudo</option>
                                    <option value="Separado">Separado</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="socialNumber">Estrato</label>
                                <select id="socialNumber" name="socialNumber" class="form-control">
                                    <option value="{{ old('socialNumber', $dataTypeContent->socialNumber ?? '') }}">
                                        {{ old('socialNumber', $dataTypeContent->socialNumber ?? '') }}
                                    </option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="rh">RH</label>
                                <input type="text" class="form-control" id="rh" name="rh" placeholder=""
                                    value="{{ old('rh', $dataTypeContent->rh ?? '') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel panel-bordered">
                        <div class="panel-body">
                            <div class="form-group col-md-6">
                                <label for="eps">EPS</label>
                                <input type="text" class="form-control" id="eps" name="eps" placeholder=""
                                    value="{{ old('eps', $dataTypeContent->eps ?? '') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="job">Ocupación</label>
                                <input type="text" class="form-control" id="job" name="job" placeholder=""
                                    value="{{ old('job', $dataTypeContent->job ?? '') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="degree">Nivel educativo</label>
                                <input type="text" class="form-control" id="degree" name="degree" placeholder=""
                                    value="{{ old('degree', $dataTypeContent->degree ?? '') }}">
                            </div>
                                <div class="form-group col-md-6 <?php if(Auth::user()->role_id == 1 || Auth::user()->role_id == 2){}else{echo 'd-none';}?>">
                                    <label for="Centro">Centro / Institución</label>
                                    @php
                                        $dataTypeRows = $dataType->{isset($dataTypeContent->id) ? 'editRows' : 'addRows'};
                                        $row = $dataTypeRows->where('field', 'user_belongsto_center_relationship')->first();
                                        $options = $row->details;
                                    @endphp
                                    @include('voyager::formfields.relationship')
                                </div>
                            <div class="form-group col-md-2">
                                <label for="isActive">Activo</label>
                                @if ($dataTypeContent->Genre !== '')
                                    <input type="checkbox" class="form-control" id="isActive" name="isActive"
                                        value="on" checked
                                        style="
                               -webkit-appearance: checkbox;
                           ">
                                @else
                                    <input type="checkbox" class="form-control" id="isActive" name="isActive"
                                        value="on"
                                        style="
                                        -webkit-appearance: checkbox;
                                    ">
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn btn-primary pull-right save">
                 Guardar
            </button>
        </form>
        <div style="display:none">
            <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
            <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $('document').ready(function() {
        $('span.select2-selection.select2-selection--single').click(function() {
 $( "li.select2-results__option").each(function(i) {
   if($(this).html()=="Super administrador"){
       $(this).remove();
   }  // do some magic with the individual element here
});
                                    });
            $('.toggleswitch').bootstrapToggle();
        });
    </script>
@stop
