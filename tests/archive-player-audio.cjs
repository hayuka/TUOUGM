/* Real HTMLAudioElement with a repository sample MP3; catalog/GA transport are fixtures. */
const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const base=process.env.TUOUGM_BASE_URL||'http://localhost:8088';
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.CHROMIUM_PATH||'/usr/bin/chromium',args:['--no-sandbox']});
 try {
  const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.addInitScript(()=>{
   const NativeAudio=window.Audio;
   window.Audio=function(...args){const audio=new NativeAudio(...args);window.sampleAudio=audio;return audio;};
   window.Audio.prototype=NativeAudio.prototype;window.sampleEvents=[];window.gtag=(...args)=>window.sampleEvents.push(args);
  });
  const tracks=['901','902'].map(id=>({id,title:'Sample '+id,lyrics:'Sample lyrics '+id,src:base+'/wp-content/themes/TUOUGM5pjax/audio/track01_01.mp3'}));
  await page.route('**/wp-json/tuougm/v1/player',route=>route.fulfill({contentType:'application/json',body:JSON.stringify({version:1,access_level:0,albums:[{id:'90',title:'Archive sample',artist_name:'LOST FIGHTS',archive_only:true,artwork:base+'/wp-content/themes/TUOUGM5pjax/img/sample_skin.webp',tracks}]})}));
  await page.goto(base+'/',{waitUntil:'networkidle'});
  await page.evaluate(()=>{const b=document.createElement('button');b.id='sample-trigger';b.className='tuougm-track-trigger';b.dataset.playerTrack='901';b.textContent='Play sample';b.style='position:fixed;top:100px;left:20px;z-index:99999';document.body.append(b);});
  assert.equal(await page.evaluate(()=>window.sampleAudio?.src||''),'');
  await page.locator('#sample-trigger').click();
  await page.waitForFunction(()=>sampleAudio&&!sampleAudio.paused&&sampleAudio.currentTime>0.1,{},{timeout:20000});
  assert.equal(await page.evaluate(()=>navigator.mediaSession.metadata.artist),'LOST FIGHTS');
  await page.evaluate(()=>{window.beforeAudio=sampleAudio;window.beforeTime=sampleAudio.currentTime;window.tuougmSwup.navigate('/archives/');});
  await page.waitForFunction(()=>location.pathname==='/archives/');
  await page.waitForFunction(()=>sampleAudio.currentTime>window.beforeTime+0.2);
  assert.equal(await page.evaluate(()=>sampleAudio===beforeAudio&&!sampleAudio.paused),true);
  console.log('PASS real MP3 decoding/playback, artist metadata, audio/time preserved across PJAX');
  await page.evaluate(()=>{sampleAudio.currentTime=sampleAudio.duration-0.1;});
  await page.waitForFunction(()=>sampleAudio.dataset.trackId==='902'&&!sampleAudio.paused);
  await page.waitForFunction(()=>Number.isFinite(sampleAudio.duration));
  await page.evaluate(()=>{sampleAudio.currentTime=sampleAudio.duration-0.1;});
  await page.waitForFunction(()=>sampleAudio.paused&&sampleAudio.ended);
  assert.equal(await page.evaluate(()=>sampleAudio.dataset.trackId),'902');
  await page.locator('.player_send.next').evaluate(e=>e.click());
  await page.waitForFunction(()=>sampleAudio.dataset.trackId==='901'&&!sampleAudio.paused);
  await page.waitForFunction(()=>sampleEvents.filter(e=>e[1]==='music_play').length>=3);
  assert.deepEqual(errors,[]);
  console.log('PASS real ended -> same archive next, final stop, manual wrap; no execution errors');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
