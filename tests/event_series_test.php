<?php

$root = dirname(__DIR__);
$dbPath = sys_get_temp_dir() . '/eventplanner-series-' . getmypid() . '.sqlite';
@unlink($dbPath);
putenv('SQLITE_PATH=' . $dbPath);
$_SERVER['SCRIPT_NAME'] = '/index.php';

$pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec((string)file_get_contents($root . '/database/schema.sql'));
$pdo->exec("INSERT INTO events (title,date,time,location,client_id,is_visible,reservations_open,reservation_capacity,slug) VALUES ('Lustre Comedy Club','2026-10-02','22:30','Lustre Bar, Braga',1,1,1,10,'lustre-comedy-club')");
$legacyEventId=(int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO event_reservations(event_id,customer_name,customer_email,tickets,status) VALUES ($legacyEventId,'Cliente antigo','old@example.test',2,'confirmed')");
$legacyReservationId=(int)$pdo->lastInsertId();
unset($pdo);

require $root . '/app/config/config.php';
require $root . '/app/config/database.php';
require $root . '/app/models/Event.php';
require $root . '/app/models/EventSeries.php';
require $root . '/app/models/Reservation.php';

function check($condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } echo "PASS: $message\n"; }

$db=(new Database())->getConnection();
$seriesModel=new EventSeries($db);
$series=$db->query("SELECT * FROM event_series WHERE slug='lustre-comedy-club'")->fetch();
check((bool)$series, 'migração cria a série Lustre');
check((int)$db->query("SELECT series_id FROM events WHERE id=$legacyEventId")->fetchColumn()===(int)$series['id'], 'evento existente fica associado à série');
check((int)$db->query("SELECT event_id FROM event_reservations WHERE id=$legacyReservationId")->fetchColumn()===$legacyEventId, 'reserva existente mantém o event_id');

$eventModel=new Event($db);
$base=['title'=>'Lustre Comedy Club','series_id'=>(int)$series['id'],'time'=>'22:30','location'=>'Lustre Bar, Braga','client_id'=>1,'is_visible'=>1,'reservations_open'=>1,'reservation_capacity'=>5,'cachet_total'=>0,'artist_map_link'=>'','artist_details'=>'','external_ticket_url'=>'','poster_url'=>'','notes'=>'','public_price_label'=>'5 € consumíveis'];
$november=$eventModel->create(array_merge($base,['date'=>'2026-11-06','slug'=>$eventModel->uniqueSlug('lustre-comedy-club-06-11-2026')]),[]);
$december=$eventModel->create(array_merge($base,['date'=>'2026-12-04','slug'=>$eventModel->uniqueSlug('lustre-comedy-club-04-12-2026')]),[]);
check(count($seriesModel->events((int)$series['id']))===3, 'novembro e dezembro aparecem automaticamente na série');

$reservationModel=new Reservation($db);
$newReservation=$reservationModel->create(['event_id'=>$november,'customer_name'=>'Teste','customer_email'=>'test@example.test','tickets'=>4,'status'=>'new']);
check((int)$db->query("SELECT event_id FROM event_reservations WHERE id=$newReservation")->fetchColumn()===$november, 'reserva de novembro usa o event_id de novembro');
check((int)$db->query("SELECT COALESCE(SUM(tickets),0) FROM event_reservations WHERE event_id=$legacyEventId AND status!='cancelled'")->fetchColumn()===2, 'lotação de outubro é independente');
check((int)$db->query("SELECT COALESCE(SUM(tickets),0) FROM event_reservations WHERE event_id=$november AND status!='cancelled'")->fetchColumn()===4, 'lotação de novembro é independente');

$eventModel->setVisibility($november,false);
check((int)$db->query("SELECT is_visible FROM events WHERE id=$november")->fetchColumn()===0, 'sessão despublicada fica invisível');
check(!$seriesModel->slugAvailable('lustre-comedy-club'), 'slug duplicado é impedido');
check($seriesModel->slugAvailable('lustre-comedy-club',(int)$series['id']), 'a própria série pode ser atualizada durante a transição');
check((bool)$eventModel->find($legacyEventId), 'evento independente/legado continua pesquisável por ID');
check((int)$db->query("SELECT COUNT(*) FROM event_series WHERE id=".(int)$series['id'])->fetchColumn()===1, 'série permanece disponível independentemente de datas futuras');

$independent=$eventModel->create(array_merge($base,['title'=>'Evento Independente','series_id'=>null,'date'=>'2027-01-10','slug'=>'evento-independente']),[]);
check($eventModel->find($independent)['slug']==='evento-independente', 'evento independente mantém slug e comportamento legado');

$source=(string)file_get_contents($root.'/app/controllers/PublicSiteController.php');
check(strpos($source,'$selectedSeries')!==false && strpos($source,'Novas datas em breve')!==false, 'página pública resolve série e estado sem novas datas');
check(strpos($source,"e.is_visible=1")!==false, 'página da série filtra eventos não publicados');
check(strpos($source,"'event_id' => \$eventId")!==false, 'handler público grava a reserva no event_id da sessão');
check(strpos($source,'@media (max-width:575.98px)')!==false, 'layout da série inclui adaptação mobile');
check(strpos($source,'^eventos/([^/]+)/([^/]+)')!==false, 'sessões têm URL individual dentro da série');

unset($db); @unlink($dbPath);
echo "Todos os testes de séries passaram.\n";
