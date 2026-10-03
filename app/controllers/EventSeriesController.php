<?php

class EventSeriesController extends BaseController
{
    public function index(): void { requireAdmin(); $series = (new EventSeries($this->db))->all(); $this->render('event_series/index', compact('series')); }
    public function create(): void { requireAdmin(); $this->render('event_series/form', ['seriesItem'=>null, 'events'=>[]]); }
    public function edit(): void
    {
        requireAdmin(); $model = new EventSeries($this->db); $id=(int)($_GET['id']??0); $seriesItem=$model->find($id);
        if (!$seriesItem) { flash('error','Série não encontrada.'); $this->redirect(BASE_URL.'?controller=eventseries'); }
        $events=$model->events($id); $this->render('event_series/form', compact('seriesItem','events'));
    }
    public function store(): void { $this->persist(null); }
    public function update(): void { $this->persist((int)($_GET['id']??0)); }

    private function persist(?int $id): void
    {
        requireAdmin(); $model=new EventSeries($this->db); $name=trim((string)($_POST['name']??'')); $slug=$this->slugify((string)($_POST['slug']??$name));
        if ($name==='' || $slug==='') { flash('error','Nome e slug são obrigatórios.'); $this->redirect(BASE_URL.'?controller=eventseries&action='.($id?'edit&id='.$id:'create')); }
        if (!$model->slugAvailable($slug,$id)) { flash('error','Este slug já está a ser usado por uma série ou evento.'); $this->redirect(BASE_URL.'?controller=eventseries&action='.($id?'edit&id='.$id:'create')); }
        $model->save($id,['name'=>$name,'slug'=>$slug,'location'=>trim((string)($_POST['location']??''))?:null,'description'=>trim((string)($_POST['description']??''))?:null,'cover_image_url'=>trim((string)($_POST['cover_image_url']??''))?:null,'is_active'=>isset($_POST['is_active'])?1:0]);
        flash('success',$id?'Série atualizada.':'Série criada.'); $this->redirect(BASE_URL.'?controller=eventseries');
    }
    private function slugify(string $value): string
    {
        $value=strtolower(trim(strtr($value,['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c'])));
        return trim((string)preg_replace('/[^a-z0-9]+/','-',$value),'-');
    }
}
