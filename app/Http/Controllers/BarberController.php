<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;

use App\Models\Barber;
use App\Models\BarberAvailability;
use App\Models\BarberReview;
use App\Models\BarberPhoto;
use App\Models\BarberService;
use App\Models\BarberTestimonial;


class BarberController extends Controller
{
    private $loggedUser;
    
    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }
   
    /*
    public function createRandom() {
        $array = ['error'=>''];

        for($q=0; $q<15; $q++) {
            $names = ['Admin', 'Paulo', 'Pedro', 'Amanda', 'Leticia', 'Gabriel', 'Gabriela', 'Thais', 'Luiz', 'Diogo', 'José', 'Jeremias', 'Francisco', 'Dirce', 'Marcelo' ];
            $lastnames = ['Adm', 'Silva', 'Santos', 'Silva', 'Alvaro', 'Sousa', 'Diniz', 'Josefa', 'Luiz', 'Diogo', 'Limoeiro', 'Santos', 'Limiro', 'Nazare', 'Mimoza' ];
            $servicos = ['Corte', 'Pintura', 'Aparação', 'Unha', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $servicos2 = ['Cabelo', 'Unha', 'Pernas', 'Pernas', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $depos = [
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
                'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.'
            ];
        
        $newBarber = new Barber();
        $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
        $newBarber->avatar = rand(1, 4).'.png';
        $newBarber->stars = rand(2, 4).'.'.rand(0, 9);
        $newBarber->latitude = '-23.5'.rand(0, 9).'30907';
        $newBarber->longitude = '-46.6'.rand(0,9).'82759';
        $newBarber->save();
        
        $ns = rand(3, 6);
        for($w=0;$w<4;$w++) {
            $newBarberPhoto = new BarberPhoto();
            $newBarberPhoto->id_barber = $newBarber->id;
            $newBarberPhoto->url = rand(1, 5).'.png';
            $newBarberPhoto->save();
        }
        
        for($w=0;$w<$ns;$w++) {
            $newBarberService = new BarberService();
            $newBarberService->id_barber = $newBarber->id;
            $newBarberService->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
            $newBarberService->price = rand(1, 99).'.'.rand(0, 100);
            $newBarberService->save();
        }
        for($w=0;$w<3;$w++) {
            $newBarberTestimonial = new BarberTestimonial();
            $newBarberTestimonial->id_barber = $newBarber->id;
            $newBarberTestimonial->name = $names[rand(0, count($names)-1)];
            $newBarberTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
            $newBarberTestimonial->body = $depos[rand(0, count($depos)-1)];
            $newBarberTestimonial->save();
        }
        for($e=0;$e<4;$e++){
            $rAdd = rand(7, 10);
            $hours = [];
        for($r=0;$r<8;$r++) {
            $time = $r + $rAdd;
        if($time < 10) {
            $time = '0'.$time;
        }
        $hours[] = $time.':00';
        }
        
        $newBarberAvail = new BarberAvailability();
        $newBarberAvail->id_barber = $newBarber->id;
        $newBarberAvail->weekday = $e;
        $newBarberAvail->hours = implode(',', $hours);
        $newBarberAvail->save();
        }
        }
            return $array;
        }
    */

    //para filtar a localização via google maps
    public function searchGeo($address) {
        //pega o dado enviado no .envs
        $key = env('MAPS_KEY', null);

        $address = urlencode($address); 

        //url do ggole maps
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
        //inicia a requisição com curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        cur_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //recebo a resposta da requisição
        $res = curl_exec($ch);//resposta
        curl_close($ch); //fecha a conexão

        return json_decode($res, true); //retorna o resultado

    }    

    public function list(Request $request) {
        $array = ['error' => ''];

        //recebo 
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $city = $request->input('city');

        //inserindo paginação com offset
        $offset = $request->input('offset');
        if(!$offset) {
            $offset = 0;
        }

        if(!empty($city)) {
            //se mandou a cidade
            $res = $this->searchGeo($city); //uso a function searchGeo

            //uso a latidude e a longitude da cidade enviada pela pessoa
            if(count($res['results']) > 0) {
                $lat = $res['results'][0]['geometry']['location']['lat'];
                $lng = $res['results'][0]['geometry']['location']['lng'];
            } 
        } elseif(!empty($lat) && !empty($lng)) {
            $res = $this->searchGeo($lat.','.$lng); //se mandar latitude e longitude

            if(count($res['results']) > 0) {
                $city = $res['results'][0]['formatted_address']; //pego o nome da cidade
            }
        } else {
            //se não mandar latitude, longitude ou cidade
            $lat = '-23.5630907';
            $lng = '-46.6682795';
            $city = 'São Paulo';
        }

        //fazer a consulta dos barbeiros perto da localização, uso o select
        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) +
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->havingRaw('distance < ?', [25]) //pesquisar barber com distancia menor que 25km
            ->orderBy('distance', 'ASC')
            ->offset($offset) //recebendo a paginação
            ->limit(5) //inserindo limete máximo para paginação
            ->get();

        foreach ($barbers as $bkey => $bvalue) {
            $barbers[$bkey]['avatar'] = url('media/avatars/'.$barbers[$bkey]['avatar']);
        }

        $array['data'] = $barbers;
        $array['loc'] = 'São Paulo';
        
        return $array;
    }

    //pegar informações de um barbeiro específico
    public function one($id) {
        $array = ['error' => ''];

        $barber = Barber::find($id);

        if($barber) {
            $barber['avatar'] = url('media/avatars/'.$barber['avatar']);
            $barber['favorited'] = false;
            $barber['photos'] = [];
            $barber['services'] = [];
            $barber['testimonials'] = [];
            $barber['available'] = [];

            //verificando favorito
            $cFavorite = UserFavorite::where('id_user', $this->loggedUser->id)
                ->where('id_barber', $barber->id)
                ->count();
            if($cFavorite > 0) {
                $barber['favorited'] = true;
            }
            

            //pegando as fotos do barber
            $barber['photos'] = BarberPhoto::select(['id', 'url'])
                ->where('id_barber', $barber->id)
                ->get();
            foreach ($barber['photos'] as $bpkey => $bpvalue) {
                $barber['photos'][$bpkey]['url'] = url('media/uploads/'.$barber['photos'][$bpkey]['url']);
            }

            //pegando os serviços do barbeiro 
            $barber['services'] = BarberService::select(['id', 'name', 'price'])
                ->where('id_barber', $barber->id)
                ->get();

            //pegando os depoimentos do barbeiro 
            $barber['testimonials'] = BarberTestimonial::select(['id', 'name', 'rate', 'body'])
                ->where('id_barber', $barber->id)
                ->get();

            //pegando disponibilidade do barbeiro 
            //2020-01-01 09:00 parrar data e hora
            $availability = [];

            // pegando a disponibilidade Crua
            $avails = BarberAvailability::where('id_barber', $barber->id)->get();
            $availWeekdays = [];
            foreach ($avails as $item) {
                $availWeekdays[$item['weekday']] = explode(',', $item['hours']);
            }

            //Pegar o agendamento dos próximos 20 dias
            $appointments = [];
            $appQuery = UserAppointment::where('id_barber', $barber->id)
                ->whereBetween('ap_datetime', [
                    date('Y-m-d').' 00:00: 00', //pega primeiro dia
                    date('Y-m-d', strtotime('+20 days')).' 23:59:59' //pego ultimo dia
                ])
                ->get();
            foreach($appQuery as $appItem) {
                $appointments[] = $appItem['ap_datetime'];
            }

            //geral disponibilidade real do barbeiro
            //iniciar com um loop de 20vezes, ou seja pegar de hoje até 20 dias 
            for($q=0;$q<20;$q++) {
                $timeItem = strtotime('+'.$q.' days'); //pega datet da hora específica
                $weekday = date('w', $timeItem); //pega o dia da semana

                //verifica se naquele dia da semana o barber possui disponibilidade
                if(in_array($weekday, array_keys($availWeekdays))) {
                    $hours = [];

                    $dayItem = date('Y-m-d', $timeItem);

                    foreach($availWeekdays[$weekday] as $hourItem) {
                        $dayFormated = $dayItem. ' '.$hourItem.':00';
                        if(!in_array($dayFormated, $appointments)) {
                            $hours[] = $hourItem;
                        }
                    }

                    //se tem horário disponível
                    if(count($hours) > 0) {
                        $availability[] = [
                            'date' => $dayItem,
                            'hours' => $hours
                        ];
                    }
                }
            } 

            $barber['available'] = $availability;

            $array['data'] = $barber;
        } else {
            $array['error'] = 'Barbeiro não existe!';
            return $array;
        }

        return $array;
    }

    public function setAppointment($id, Request $request) {
        //service, year, month, day, hour
        $array = ['error' => ''];
        
        $service = $request->input('service');
        $year = intval($request->input('year'));
        $month = intval($request->input('month'));
        $day = intval($request->input('day'));
        $hour = intval($request->input('hour'));

        $month = ($month < 10) ? '0'.$month : $month;
        $day = ($day < 10) ? '0'.$day : $day;
        $hour = ($hour < 10) ? '0'.$hour : $hour;

        // 1. verificar se o serviço do barbeiro existe
        $barberservice = BarberService::select()
            ->where('id', $service)
            ->where('id_barber', $id)
        ->first();

        if($barberservice) {
            // 2. verificar se a data é real 
            $apDate = $year.'-'.$month.'-'.$day. ' '.$hour.':00:00';
            if(strtotime($apDate) > 0) {
                // 3. verificar se o barbeiro possui agendamento neste dia/hora
                $apps = UserAppointment::select()
                    ->where('id_barber', $id)
                    ->where('ap_datetime', $apDate)
                ->count();
                if($apps === 0) {
                    // 4.1 verificar se o barbeiro atende nesta data
                    $weekday = date('w', strtotime($apDate));
                    $avail = BarberAvailability::select()
                        ->where('id_barber', $id)
                        ->where('weekday', $weekday)
                    ->first();
                    if($avail) {
                        // 4.2 verificar se  barbeiro atende nesta hora
                        $hours = explode(',', $avail['hours']);
                        if(in_array($hour.':00', $hours)) {
                            // 5. fazer o agendamento
                            $newApp = new UserAppointment();
                            $newApp->id_user = $this->loggedUser->id;
                            $newApp->id_barber = $id;
                            $newApp->id_service = $service;
                            $newApp->ap_datetime = $apDate;
                            $newApp->save();
                        } else {
                            $array['error'] = 'Barbeiro não atende nesta hora.';
                        }
                    } else {
                        $array['error'] = 'Barbeiro não atende neste dia.';
                    }
                } else {
                    $array['error'] = 'Barbeiro já possui agendamento neste dia/hora!';
                }
            } else {
                $array['error'] = 'Data inválide!';
            }
        } else {
            $array['error'] = 'Serviço inexistente!';
        }
        return $array;
    }

    public function search(Request $request) {
        $array = ['error'=>'', 'list'=>[]];

        $q = $request->input('q');

        if($q) {
            $barbers = Barber::select()
                ->where('name', 'LIKE', '%'.$q.'%')
            ->get();

            foreach($barbers as $bkey => $barber) {
                $barbers[$bkey]['avatar'] = url('media/avatars/'.$barbers[$bkey]['avatar']);
            }

            $array['list'] = $barbers;
        } else {
            $array['error'] = 'Digite algo para buscar!';
        }

        return $array;
    }
}
