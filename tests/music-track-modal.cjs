/* Browser transport fixtures only; no WordPress DB writes. See docs/music-track-modal.md. */
const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert/strict');
const fixtures = JSON.parse(fs.readFileSync(process.env.TUOUGM_UI_FIXTURES || '/tmp/tuougm-ui-fixtures.json'));
const base = process.env.TUOUGM_BASE_URL || 'http://localhost:8088';
const escape = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const albums = [...new Set(fixtures.map(f=>f.album_id))];
const markup = albums.map(album=>'<section class="music_album" data-detail-album="'+album+'"><h2>Fixture album '+album+'</h2><ol class="music_track_list">'+fixtures.filter(f=>f.album_id===album).map(f=>'<li class="music_track_item"><a class="music_track_title" data-detail-track="'+f.track_id+'" href="'+escape(f.url)+'">'+escape(f.title)+'</a><button class="tuougm-track-trigger music_track_play" data-player-track="'+f.track_id+'">Play</button></li>').join('')+'</ol></section>').join('');
(async()=>{
 const browser = await chromium.launch({executablePath:process.env.CHROMIUM_PATH || '/usr/bin/chromium',args:['--no-sandbox']});
 let mode='success', delay=0, calls=[];
 async function setup(page,disabled=false){
  await page.route('**/en/music/**',async route=>{
   const response=await route.fetch();
   const html=(await response.text()).replace('<div class="wrap_sub_cnt music_list">','<div class="wrap_sub_cnt music_list">'+markup);
   await route.fulfill({response,body:html});
  });
  await page.route('**/wp-json/tuougm/v1/track-detail*',async route=>{
   const url=new URL(route.request().url());calls.push(Object.fromEntries(url.searchParams));
   const item=fixtures.find(f=>String(f.track_id)===url.searchParams.get('track_id')&&String(f.album_id)===url.searchParams.get('album_id'));
   const captured=mode;
   if(delay) await new Promise(r=>setTimeout(r,delay));
   try { await route.fulfill({status:captured==='failure'?500:200,contentType:'application/json',body:JSON.stringify(captured==='failure'?{message:'fixture failure'}:item)}); } catch {}
  });
  if(disabled) await page.route('**/js/music-track-modal.js*',route=>route.fulfill({contentType:'application/javascript',body:'/* deliberately uninitialized */'}));
 }
 const page=await browser.newPage({viewport:{width:1440,height:900}});const errors=[];page.on('pageerror',e=>errors.push(e.message));await setup(page);
 await page.goto(base+'/en/music/',{waitUntil:'networkidle'});
 const first=page.locator('.music_track_title').first();
 await first.scrollIntoViewIfNeeded();
 const saved=await page.evaluate(()=>{window.testPlayer=document.querySelector('.player');return {scroll:scrollY,url:location.href,title:document.title,canonical:document.querySelector('link[rel="canonical"]').href};});
 await first.click();await page.waitForSelector('#music-modal-track-title');
 assert.equal(await page.locator('[data-track-prev]').isDisabled(),true);
 assert.match(await page.locator('[data-track-next]').innerText(),/First/);
 assert.equal(await page.evaluate(()=>document.querySelector('.player')===window.testPlayer),true);
 assert.deepEqual(await page.evaluate(()=>({url:location.href,title:document.title,canonical:document.querySelector('link[rel="canonical"]').href})),{url:saved.url,title:saved.title,canonical:saved.canonical});
 await page.waitForTimeout(800);
 await page.screenshot({path:'/tmp/tuougm-modal-desktop.png'});
 await page.locator('[data-track-next]').click();await page.waitForFunction(()=>document.querySelector('#music-modal-track-title')?.textContent==='First');
 await page.keyboard.press('ArrowRight');await page.waitForFunction(()=>document.querySelector('#music-modal-track-title')?.textContent==='Second');
 assert.equal(await page.locator('[data-track-next]').isDisabled(),true);
 for(let i=0;i<15;i++){await page.keyboard.press('Tab');assert.equal(await page.evaluate(()=>document.activeElement.closest('.music-track-modal')!==null),true);}
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 assert.equal(await page.evaluate(()=>document.activeElement===document.querySelector('.music_track_title')),true);
 assert.ok(Math.abs((await page.evaluate(()=>scrollY))-saved.scroll)<2);
 assert.equal(await page.evaluate(()=>document.querySelector('#swup').inert),false);
 console.log('PASS desktop, DOM album order, endpoints, keyboard, focus, scroll, head/player preservation');
 // Failure leaves a real standalone link and supports retry.
 mode='failure';await first.click();await page.waitForFunction(()=>document.querySelector('.music_track_modal_content button')?.textContent==='Retry');
 assert.equal(await page.locator('.music_track_modal_page').getAttribute('href'),fixtures[0].url);
 mode='success';await page.locator('.music_track_modal_content>button').click();await page.waitForSelector('#music-modal-track-title');
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 console.log('PASS request failure and retry');
 // A newer detail wins even when an earlier response arrives later.
 delay=800;await first.click();delay=0;await page.locator('[data-track-next]').evaluate(e=>e.click());
 await page.waitForFunction(()=>document.querySelector('#music-modal-track-title')?.textContent==='First');
 await page.waitForTimeout(900);assert.equal(await page.locator('#music-modal-track-title').innerText(),'First');
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 // Shorten only our request timeout, leaving Modaal's own animation timers alone.
 await page.evaluate(()=>{window.testNativeTimeout=window.setTimeout;window.setTimeout=(fn,ms,...args)=>window.testNativeTimeout(fn,ms===15000?30:ms,...args);});
 delay=200;await first.click();await page.waitForFunction(()=>document.querySelector('.music_track_modal_content button')?.textContent==='Retry');
 await page.evaluate(()=>window.setTimeout=window.testNativeTimeout);delay=0;
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 console.log('PASS stale response suppression and timeout fallback');
 // No wrapping into the next album (its single track has both ends disabled).
 await page.locator('.music_track_title').last().click();await page.waitForSelector('#music-modal-track-title');
 assert.equal(await page.locator('[data-track-prev]').isDisabled(),true);assert.equal(await page.locator('[data-track-next]').isDisabled(),true);
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 // Close while awaiting a response: it must never recreate content.
 delay=700;await first.click();await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});await page.waitForTimeout(800);assert.equal(await page.locator('#music-modal-track-title').count(),0);delay=0;
 console.log('PASS album boundary and close during request');
 // Revisit through existing Swup; controller and click handler remain single.
 await page.evaluate(()=>window.tuougmSwup.navigate('/archives/'));await page.waitForFunction(()=>location.pathname==='/archives/');await page.waitForTimeout(500);
 await page.evaluate(()=>window.tuougmSwup.navigate('/en/music/'));await page.waitForSelector('.music_track_title[data-no-swup]');
 const before=calls.length;await page.locator('.music_track_title').first().click();await page.waitForSelector('#music-modal-track-title');assert.equal(calls.length,before+1);
 await page.evaluate(()=>window.tuougmSwup.navigate('/archives/'));await page.waitForFunction(()=>location.pathname==='/archives/');await page.waitForTimeout(500);
 assert.equal(await page.locator('.music-track-modal').count(),0);assert.equal(await page.evaluate(()=>document.querySelector('#swup').inert),false);
 console.log('PASS PJAX revisit, single request, navigation cleanup');
 // Mobile frame uses real CSS; transport still uses generated detail HTML.
 await page.setViewportSize({width:390,height:844});await page.goto(base+'/en/music/',{waitUntil:'networkidle'});await page.locator('.music_track_title').first().click();await page.waitForSelector('#music-modal-track-title');
 await page.waitForTimeout(800);
 await page.screenshot({path:'/tmp/tuougm-modal-mobile.png'});
 const rect=await page.locator('.music-track-modal .modaal-container').boundingBox();assert.ok(rect.width>=389&&rect.x===0);assert.ok(rect.height>=844);
 await page.keyboard.press('Escape');await page.waitForSelector('.music-track-modal',{state:'detached'});
 await page.screenshot({path:'/tmp/tuougm-modal-mobile-closed.png'});
 console.log('PASS mobile fullscreen');
 assert.deepEqual(errors,[]);
 const fallback=await browser.newPage();await setup(fallback,true);await fallback.goto(base+'/en/music/',{waitUntil:'networkidle'});
 assert.equal(await fallback.locator('.music_track_title[data-no-swup]').count(),0);
 await fallback.locator('.music_track_title').first().click();await fallback.waitForFunction(()=>location.pathname.includes('/tracks/'));
 assert.equal(await fallback.locator('.music-track-modal').count(),0);console.log('PASS uninitialized JS retains normal detail navigation');
 await browser.close();console.log('PASS no execution errors');
})().catch(error=>{console.error(error);process.exit(1);});
