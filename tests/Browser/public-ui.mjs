// Run with PLAYWRIGHT_MODULE pointing to an installed playwright entrypoint.
// Fixtures replace only initial Inertia document props. Assets/components are real;
// vote responses are mocked. No fixture or vote is written to the application DB.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ? pathToFileURL(process.env.PLAYWRIGHT_MODULE).href : 'playwright');
const base = process.env.REVIEW_URL || 'http://127.0.0.1:8017';
const output = path.resolve(process.env.REVIEW_OUTPUT || '/tmp/hackathon-lote-a-evidence');
fs.mkdirSync(output, { recursive: true });
const html = await (await fetch(base)).text();
const decode = value => value.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
const encode = value => value.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
const original = JSON.parse(decode(html.match(/data-page="([^"]+)"/)[1]));
const manifest = JSON.parse(fs.readFileSync('public/build/manifest.json', 'utf8'));
const app = manifest['resources/js/app.tsx'];
const styles = [...(app.css || []), ...(manifest['resources/css/app.css'] ? [manifest['resources/css/app.css'].file] : [])];
const template = html.replace(/<script\b[^>]*type="module"[^>]*>[\s\S]*?<\/script>/g, '')
    .replace(/<link\b[^>]*(?:5173|@vite)[^>]*>/g, '')
    .replace('</head>', styles.map(file=>'<link rel="stylesheet" href="/build/'+file+'">').join('') + '<script type="module" src="/build/'+app.file+'"></script></head>');
const user = {id:99999,name:'Pessoa de Teste',email:'teste@example.org',email_verified_at:'2026-09-01T00:00:00Z'};
const shared = { auth: {user:null,is_staff:false,is_admin:false,is_judge:false}, evento:null, flash:{}, errors:{}, app_version:'review', name:'Hackathon IFPR' };
const event = {nome:'Hackathon IFPR Pinhais',descricao:'Tecnologia e colaboração para transformar ideias em soluções para a comunidade.',edicao:1,situacao:'published',situacao_label:'Inscrições abertas',inicia_em:'2026-10-15T12:00:00Z',termina_em:'2026-10-16T21:00:00Z',inscricoes_abrem_em:null,inscricoes_fecham_em:null,inscricoes_abertas:true};
const projects=[{id:1,titulo:'Monitoramento de qualidade da água para comunidades',equipe:'Equipe Conexões',resumo:'Uma solução acessível para acompanhar indicadores e avisar moradores sobre mudanças na qualidade da água.'},{id:2,titulo:'Mobilidade inclusiva',equipe:'Equipe Caminhos',resumo:'Rotas acessíveis e colaboração entre moradores.'}];
const row=(posicao)=>({posicao,titulo:projects[(posicao-1)%2].titulo,equipe:'Equipe '+posicao,nota_final:9.65-posicao/10,trilha:'Tecnologia social'});
const criteria=[{id:1,nome:'Impacto social',descricao:'Benefícios concretos para a comunidade.',peso:3,nota_maxima:10},{id:2,nome:'Execução e originalidade',descricao:'Qualidade da solução e clareza da proposta.',peso:2,nota_maxima:10}];
const voteProps={evento:{nome:event.nome},submissoes:projects,votacao_aberta:true,pode_votar:true,ja_votou_em:null};
const agendaProps={evento:event,itens:[{id:1,titulo:'Abertura e apresentação do desafio',descricao:'Conheça o desafio e as pessoas que vão construir com você.',tipo:'palestra',tipo_label:'Palestra',destaque:false,inicia_em:'2026-10-15T12:00:00Z',termina_em:'2026-10-15T13:00:00Z',local:'Auditório',palestrante:'Equipe de organização',trilha:null},{id:2,titulo:'Entrega dos projetos',descricao:null,tipo:'deadline',tipo_label:'Prazo',destaque:true,inicia_em:'2026-10-16T20:00:00Z',termina_em:'2026-10-16T21:00:00Z',local:null,palestrante:null,trilha:{nome:'Tecnologia social',cor:'#fff'}}]};
const cases=[
 ['inicio','/',{evento:event,estatisticas:{inscritos:120,equipes:30,trilhas:3}}],
 ['agenda','/agenda',agendaProps],
 ['projetos','/projetos',voteProps],
 ['rubrica','/rubrica',{evento:event,criterios:criteria}],
 ['regulamento','/regulamento',{evento:{nome:event.nome,min_team_size:3,max_team_size:5,submission_deadline:'16/10/2026 às 18:00'},regulamento:{tem_arquivo:true,atualizado_em:'01/09/2026'}}],
 ['resultados','/resultados',{publicado:true,evento:event,podio_geral:[row(1),row(1),row(3)],podio_por_trilha:{'Tecnologia social':[row(1),row(2)]},premio_popular:{titulo:'Mobilidade inclusiva',equipe:'Equipe Caminhos',votos:42}}],
 ['edicoes','/edicoes',{edicoes:[{nome:'Hackathon IFPR — primeira edição',edicao:1,slug:'primeira',encerrado_em:'2026-08-20T20:00:00Z'}]}],
 ['validar','/validar/00000000-0000-4000-8000-000000000001',{encontrado:true,nome:'Participante de Teste',tipo_label:'Participante',evento:event.nome,carga_horaria:20,emitido_em:'20/08/2026'}],
 ['privacidade','/privacidade',{}],['cookies','/cookies',{}],
];
const browser=await chromium.launch({headless:true});
const context=await browser.newContext();
const page=await context.newPage();
const errors=[];
page.on('pageerror',err=>errors.push(err.message));
let current=cases[0];
let extra={};
let responseProps=null;
let requests=0;
let release;
const getPage=()=>({...original,component:'publico/'+current[0],url:current[1],props:{...shared,...current[2],...extra}});
await page.route(base+'/**',async route=>{
 const request=route.request();
 if(request.isNavigationRequest() && request.resourceType()==='document'){
  await route.fulfill({contentType:'text/html',body:template.replace(/data-page="[^"]+"/,'data-page="'+encode(JSON.stringify(getPage()))+'"')});
 }else if(request.method()==='POST' && new URL(request.url()).pathname==='/votos'){
  requests++;
  await new Promise(resolve=>{release=resolve;});
  await route.fulfill({contentType:'application/json',headers:{'X-Inertia':'true'},body:JSON.stringify({...getPage(),props:{...getPage().props,...responseProps}})});
 }else await route.continue();
});
const report={viewports:[],checks:[],pageErrors:errors};
async function open(test, props={}){
 current=test;extra=props;
 await page.goto(base+test[1]);
 await page.locator('main h1').waitFor();
 await page.evaluate(()=>document.fonts.ready);
}
try{
 for(const width of [320,390,768,1440]){
  await page.setViewportSize({width,height:900});
  for(const theme of ['light','dark']){
   await page.emulateMedia({colorScheme:theme,reducedMotion:'reduce'});
   await page.addInitScript(value=>{localStorage.setItem('appearance',value);document.cookie='cookie_consent=aceito; path=/; samesite=lax';},theme);
   for(const test of cases){
    await open(test,test[0]==='projetos'?{auth:{...shared.auth,user}}:{});
    await page.evaluate(value=>document.documentElement.classList.toggle('dark',value==='dark'),theme);
    const size=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));
    assert.equal(size.width,width);
    assert.ok(size.scroll<=width, test[0]+' overflows at '+width+': '+size.scroll);
    const file=test[0]+'-'+width+'-'+theme+'.png';
    if(width===390||width===1440)await page.screenshot({path:path.join(output,file),fullPage:true});
    report.viewports.push({page:test[0],width,theme,overflow:false,screenshot:width===390||width===1440?file:null});
   }
  }
 }
 await page.setViewportSize({width:390,height:844});
 await open(cases[1]);
 await page.getByLabel('Atividade',{exact:true}).selectOption('deadline');
 assert.equal(await page.locator('main ol li').count(),1);
 await page.getByLabel('Dia',{exact:true}).selectOption('2026-10-15');
 await page.getByText('Nenhuma atividade com estes filtros',{exact:true}).waitFor();
 await page.getByRole('button',{name:'Limpar filtros',exact:true}).click();
 assert.equal(await page.locator('main ol li').count(),2);
 report.checks.push('Agenda: type/day filters, zero results, reset');
 await open(cases[2],{auth:{...shared.auth,user}});
 await page.getByLabel('Buscar projeto ou equipe').fill('inexistente');
 await page.getByText('Nenhum projeto encontrado',{exact:true}).waitFor();
 await page.getByRole('button',{name:'Limpar busca',exact:true}).click();
 const voteButtons=page.getByRole('button',{name:/^Votar em /});
 await voteButtons.first().click();
 await page.getByRole('button',{name:'Voltar aos projetos',exact:true}).click();
 assert.equal(await voteButtons.first().evaluate(el=>el===document.activeElement),true);
 await voteButtons.first().click();
 responseProps={errors:{submission_id:'Voto recusado para teste.'}};
 await page.getByRole('button',{name:'Confirmar voto',exact:true}).click();
 await page.waitForFunction(()=>document.querySelector('button[disabled]'));
 assert.equal(await voteButtons.evaluateAll(buttons=>buttons.every(button=>button.disabled)),true);
 await page.getByRole('button',{name:'Registrando…',exact:true}).evaluate(el=>{el.click();el.click();});
 assert.equal(requests,1);
 release();
 await page.getByText('Voto recusado para teste.',{exact:true}).waitFor();
 responseProps={errors:{},ja_votou_em:1};
 await page.getByRole('button',{name:'Confirmar voto',exact:true}).click();
 await page.waitForTimeout(200);
 assert.equal(requests,2);
 release();
 await page.getByText('Seu voto',{exact:true}).waitFor();
 await page.getByRole('dialog').waitFor({state:'hidden'});
 report.checks.push('Voting: search, cancel/focus, all buttons disabled, duplicate click, server error, retry, success');
 for(const [name,props,text] of [
 ['inicio',{evento:null,estatisticas:null},'O próximo encontro começa com uma ideia.'],
 ['agenda',{evento:null,itens:[]},'Agenda ainda não publicada'],
 ['projetos',{...voteProps,submissoes:[]},'Nenhum projeto enviado ainda'],
 ['rubrica',{evento:null,criterios:[]},'Rubrica ainda não publicada'],
 ['resultados',{publicado:false,evento:null,podio_geral:[],podio_por_trilha:{},premio_popular:null},'Resultado ainda não publicado'],
 ['edicoes',{edicoes:[]},'Nenhuma edição encerrada ainda'],
 ['validar',{encontrado:false},'Certificado não encontrado'],
 ]){
  const test=cases.find(item=>item[0]===name);
  await open([name,test[1],props]);
  await page.getByText(text,{exact:true}).waitFor();
  report.checks.push(name+': empty/unavailable state');
 }
 await open(cases[2],{ja_votou_em:1,votacao_aberta:false,pode_votar:false});
 await page.getByText('Seu voto',{exact:true}).waitFor();
 assert.equal(await page.getByRole('button',{name:/^Votar em /}).count(),0);
 await open(cases[0]);
 await page.keyboard.press('Tab');await page.keyboard.press('Enter');
 assert.equal(await page.evaluate(()=>document.activeElement.id),'conteudo-publico');
 await page.getByRole('button',{name:'Abrir menu',exact:true}).click();
 await page.getByRole('dialog').waitFor();
 await page.keyboard.press('Escape');
 await page.getByRole('dialog').waitFor({state:'hidden'});
 await open(cases[8]);
 await page.getByRole('link',{name:/Seus direitos e contato/}).click();
 assert.equal(await page.evaluate(()=>document.activeElement.id),'secao-6');
 report.checks.push('Closed voting preserves receipt; skip link; mobile menu; document section keyboard target');
 assert.deepEqual(errors,[]);
 console.log(JSON.stringify({viewports:report.viewports.length,checks:report.checks.length,output},null,2));
}finally{
 fs.writeFileSync(path.join(output,'report.json'),JSON.stringify(report,null,2));
 await browser.close();
}
