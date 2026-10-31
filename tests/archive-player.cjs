/* Real theme/Swup in Chromium; Audio, Media Session and API are deterministic doubles. */
const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const base=process.env.TUOUGM_BASE_URL || 'http://localhost:8088';
const fallback='THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN';
const track=(id)=>({id,title:'Track '+id,src:base+'/fixture-'+id+'.mp3',lyrics:'Lyrics '+id});
const album=(id,archive,ids,artist=fallback)=>({id,title:'Album '+id,archive_only:archive,artist_name:artist,artwork:base+'/wp-content/themes/TUOUGM5pjax/img/sample_skin.webp',tracks:ids.map(track)});
const normalA=album('10',false,['101','102']),normalB=album('20',false,['201']);
const archiveA=album('30',true,['301','302'],'LOST FIGHTS'),archiveB=album('40',true,['401'],'Another band');
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM_PATH||'/usr/bin/chromium',args:['--no-sandbox']});
 try {
  async function setup(albums){
   const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));let requests=0;
   await page.addInitScript(()=>{
    window.audioInstances=[];window.musicEvents=[];window.mediaHandlers={};
    window.gtag=(...args)=>window.musicEvents.push(args);
    window.Audio=class extends EventTarget {
     constructor(){super();this.dataset={};this.paused=true;this.currentTime=0;this.duration=120;this.src='';this.error=null;this.playCalls=0;window.audioInstances.push(this);}
     removeAttribute(name){if(name==='src')this.src='';}
     load(){}
     play(){this.playCalls++;this.paused=false;this.dispatchEvent(new Event('playing'));return Promise.resolve();}
     pause(){this.paused=true;this.dispatchEvent(new Event('pause'));}
    };
    window.MediaMetadata=class {constructor(data){Object.assign(this,data);}};
    Object.defineProperty(navigator,'mediaSession',{configurable:true,value:{metadata:null,setActionHandler(name,fn){window.mediaHandlers[name]=fn;}}});
   });
   await page.route('**/wp-json/tuougm/v1/player',route=>{requests++;return route.fulfill({contentType:'application/json',body:JSON.stringify({version:1,access_level:0,albums})});});
   await page.goto(base+'/',{waitUntil:'networkidle'});
   await page.waitForFunction(()=>!!document.querySelector('.player_repeat'));
   await page.evaluate(()=>{
    const container=document.createElement('div');container.id='archive-fixture-buttons';
    for(const id of ['101','102','201','301','302','401']){const b=document.createElement('button');b.id='trigger-'+id;b.className='tuougm-track-trigger';b.dataset.playerTrack=id;b.textContent=id;container.append(b);}
    document.body.append(container);
   });
   return {page,errors,requests:()=>requests};
  }
  const ctx=await setup([archiveA,normalA,archiveB,normalB]);const p=ctx.page;
  const state=()=>p.evaluate(()=>({id:audioInstances[0]?.dataset.trackId,paused:audioInstances[0]?.paused,title:document.querySelector('.player_title span').textContent,album:document.querySelector('.player_album_title').textContent,artist:navigator.mediaSession.metadata?.artist,lyrics:document.querySelector('.player_lyrics_content').textContent,playing:document.querySelector('.player_track_list li.playing')?.dataset.playerTrack}));
  const settle=()=>p.waitForTimeout(30);
  const trigger=async id=>{await p.locator('#trigger-'+id).evaluate(e=>e.click());await settle();};
  const next=async()=>{await p.locator('.player_send.next').evaluate(e=>e.click());await settle();};
  const ended=async()=>{await p.evaluate(()=>{const a=audioInstances[0];a.paused=true;a.dispatchEvent(new Event('ended'));});await settle();};
  const media=async name=>{await p.evaluate(name=>mediaHandlers[name](),name);await settle();};
  assert.deepEqual(await p.locator('.player_album_options input').evaluateAll(es=>es.map(e=>e.value)),['10','20']);
  assert.equal((await state()).album,'Album 10');assert.equal(await p.evaluate(()=>audioInstances.every(a=>a.playCalls===0&&!a.src)),true);
  console.log('PASS normal-only selector/initial selection; no autoplay or audio load');
  await trigger('301');assert.equal((await state()).id,'301');assert.equal((await state()).artist,'LOST FIGHTS');assert.equal((await state()).lyrics,'Lyrics 301');assert.equal((await state()).playing,'301');
  assert.equal(await p.locator('.player_album_options input:checked').count(),0);
  const count=await p.evaluate(()=>musicEvents.length);
  await trigger('301');assert.equal((await state()).paused,true);await trigger('301');assert.equal((await state()).paused,false);assert.equal(await p.evaluate(()=>musicEvents.length),count);
  assert.deepEqual(await p.evaluate(()=>musicEvents[0]),['event','music_play',{album_id:'30',album_name:'Album 30',track_id:'301',track_name:'Track 301'}]);
  await p.evaluate(()=>{const a=audioInstances[0];a.currentTime=30;a.dispatchEvent(new Event('timeupdate'));});
  assert.equal(await p.locator('.time_current').innerText(),'0:30');assert.match(await p.locator('.seek_progress').getAttribute('style'),/25%/);
  console.log('PASS article playback/toggle, archive UI/artwork context, lyrics/seek, artist and GA dedup/contract');
  await ended();assert.equal((await state()).id,'302');await ended();assert.equal((await state()).id,'302');assert.equal((await state()).paused,true);
  await next();assert.equal((await state()).id,'301');await next();assert.equal((await state()).id,'302');
  await p.locator('.player_repeat').evaluate(e=>e.click());await ended();assert.equal((await state()).id,'301');
  await media('nexttrack');assert.equal((await state()).id,'302');await media('nexttrack');assert.equal((await state()).id,'301');
  await media('pause');assert.equal((await state()).paused,true);await media('play');assert.equal((await state()).paused,false);
  await next();await media('previoustrack');assert.equal((await state()).id,'301');
  await p.locator('.player_repeat').evaluate(e=>e.click());
  await trigger('401');await ended();assert.equal((await state()).paused,true);await next();assert.equal((await state()).id,'401');await media('nexttrack');assert.equal((await state()).id,'401');
  console.log('PASS archive ended/repeat/manual Next/Media Session boundaries, Previous, single-track archive');
  await p.locator('.player_album_options input[value="10"]').evaluate(e=>{e.checked=true;e.dispatchEvent(new Event('change',{bubbles:true}));});await settle();
  assert.equal((await state()).id,'101');assert.equal((await state()).artist,fallback);
  await ended();assert.equal((await state()).id,'102');await ended();assert.equal((await state()).id,'201');await media('nexttrack');assert.equal((await state()).id,'101');
  await next();await next();assert.equal((await state()).id,'201');
  await p.locator('.player_repeat').evaluate(e=>e.click());await ended();assert.equal((await state()).id,'201');
  await next();assert.equal((await state()).id,'101');await p.locator('.player_repeat').evaluate(e=>e.click());
  console.log('PASS switch to normal album, normal sequencing skips archives, normal repeat/manual semantics');
  await trigger('301');const before=await p.evaluate(()=>{window.savedAudio=audioInstances[0];window.savedPlayer=document.querySelector('.player');return musicEvents.length;});
  await p.evaluate(()=>window.tuougmSwup.navigate('/archives/'));await p.waitForFunction(()=>location.pathname==='/archives/');await p.waitForTimeout(500);
  assert.equal(await p.evaluate(()=>audioInstances[0]===savedAudio&&document.querySelector('.player')===savedPlayer&&!savedAudio.paused),true);
  assert.equal(await p.evaluate(()=>musicEvents.length),before);assert.equal(ctx.requests(),1);assert.deepEqual(ctx.errors,[]);
  console.log('PASS PJAX audio/player/state preservation, no duplicate API request or GA event');
  const only=await setup([archiveA,archiveB]);assert.equal(await only.page.locator('.player_album_options input').count(),0);assert.equal(await only.page.locator('.player_title span').innerText(),'No track selected');
  assert.equal(await only.page.evaluate(()=>audioInstances.every(a=>a.playCalls===0&&!a.src)),true);
  await only.page.locator('#trigger-401').evaluate(e=>e.click());await only.page.waitForTimeout(30);assert.equal(await only.page.evaluate(()=>audioInstances[0].dataset.trackId),'401');assert.deepEqual(only.errors,[]);
  const empty=await setup([]);assert.equal(await empty.page.locator('.player_title span').innerText(),'No track selected');assert.deepEqual(empty.errors,[]);
  console.log('PASS archive-only and empty catalogs wait for user operation; no execution errors');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
