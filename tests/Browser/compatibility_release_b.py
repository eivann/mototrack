"""Release B headless-browser check (Playwright, Python). Not part of `php artisan test`.
Runs against a LOCAL throwaway app: sqlite DB, owner owner@uat.test, `php artisan serve --port=8765`
with RECAPTCHA_SKIP_IP=127.0.0.1. Never point it at production.
"""
from playwright.sync_api import sync_playwright
import json,sys
B='http://127.0.0.1:8765'
res=[]
def ok(name,cond,extra=''):
    res.append((name,bool(cond),extra)); print(('PASS' if cond else 'FAIL'),name,extra)
import sqlite3 as _s
_c=_s.connect('/tmp/claude-0/uat/uat.sqlite'); _c.execute('delete from part_compatibility'); _c.execute('delete from motorcycles'); _c.commit(); _c.close()
with sync_playwright() as p:
    br=p.chromium.launch()
    ctx=br.new_context(viewport={'width':1400,'height':1000})
    pg=ctx.new_page()
    errs=[]
    pg.on('pageerror',lambda e:errs.append('PAGEERR '+str(e)[:150]))
    pg.on('console',lambda m:errs.append('CONSOLE '+m.text[:150]) if (m.type=='error' and 'Failed to load resource' not in m.text and 'Content Security' not in m.text) else None)
    import sqlite3
    pg.goto(B+'/login'); pg.fill('#email','owner@uat.test'); pg.fill('#password','secret-pass-123')
    with pg.expect_navigation(wait_until='networkidle'):
        pg.click('button:has-text("Sign In")')
    c=sqlite3.connect('/tmp/claude-0/uat/uat.sqlite'); c.execute("update users set otp_code=?, otp_expires_at=datetime('now','+24 hours') where email='owner@uat.test'",('$2y$10$A3kwwPITV4O0UWqA9Ebs/eyMTycgRL3dhOIQZdHegghzJxXmpqc4C',)); c.commit(); c.close()
    pg.fill('#otp_code','123456')
    with pg.expect_navigation(wait_until='networkidle'):
        pg.click('button[type=submit]:has-text("Verify")') if pg.locator('button[type=submit]:has-text("Verify")').count() else pg.keyboard.press('Enter')
    errs.clear(); pg.goto(B+'/compatibility/manage'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(500)
    ok('manage page loads with zero console/page errors',not errs,str(errs))
    print('URL',pg.url,'|',pg.inner_text('body')[:200].replace('\n',' '))
    modal=pg.locator('[role=dialog]')
    ok('modal hidden initially',not modal.is_visible())
    # open add
    pg.locator('button:has-text("Add Fitment")').first.click(); pg.wait_for_timeout(300)
    ok('Add Fitment opens modal',modal.is_visible())
    ok('modal shows part name',pg.locator('#fitment-modal-title + p').inner_text().strip()!='')
    ok('no errors after open',not errs,str(errs))
    # type then cancel -> reset
    pg.fill('#fit-brand','Zzz'); pg.click('button:has-text("Cancel")'); pg.wait_for_timeout(200)
    ok('Cancel closes modal',not modal.is_visible())
    pg.locator('button:has-text("Add Fitment")').first.click(); pg.wait_for_timeout(200)
    ok('modal resets after cancel',pg.input_value('#fit-brand')=='' and pg.input_value('#fit-model')=='')
    pg.keyboard.press('Escape'); pg.wait_for_timeout(200)
    ok('Escape closes modal',not modal.is_visible())
    # save two models, lowercase brand
    pg.locator('button:has-text("Add Fitment")').first.click(); pg.wait_for_timeout(200)
    pg.fill('#fit-brand','honda'); pg.fill('#fit-model','TMX 125, TMX 155')
    pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(300)
    body=pg.inner_text('body')
    ok('save adds two fitments with success message','Fitment added: honda TMX 125, honda TMX 155' in body,body[body.find('Fitment added'):][:90])
    ok('modal closed after save',not modal.is_visible())
    # second part same brand different case -> canonical spelling reused
    pg.locator('button:has-text("Add Fitment")').nth(1).click(); pg.wait_for_timeout(200)
    pg.fill('#fit-brand','HONDA'); pg.fill('#fit-model','tmx 125')
    pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle')
    ok('brand/model casing reuses existing record','Fitment added: honda TMX 125' in pg.inner_text('body'),pg.inner_text('body')[:0])
    # validation failure reopens modal with typed values
    pg.locator('button:has-text("Add Fitment")').nth(2).click(); pg.wait_for_timeout(200)
    pg.fill('#fit-brand','Yamaha'); pg.fill('#fit-model','Sniper'); pg.fill('#fit-year','abc')
    pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(400)
    ok('validation error reopens modal with typed values',modal.is_visible() and pg.input_value('#fit-brand')=='Yamaha' and pg.input_value('#fit-year')=='abc',str(modal.is_visible()))
    ok('validation error message shown','single year' in pg.inner_text('body').lower())
    pg.fill('#fit-year',''); pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle')
    ok('corrected form saves','Fitment added: Yamaha Sniper' in pg.inner_text('body'))
    # duplicate
    pg.locator('button:has-text("Add Fitment")').first.click(); pg.wait_for_timeout(200)
    pg.fill('#fit-brand','Honda'); pg.fill('#fit-model','TMX 125')
    pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(300)
    ok('duplicate rejected with message','already compatible' in pg.inner_text('body'))
    pg.keyboard.press('Escape')
    # edit note
    pg.locator('button:has-text("Edit")').first.click(); pg.wait_for_timeout(200)
    ok('edit modal opens read-only motorcycle fields',modal.is_visible() and pg.locator('#fit-brand').is_disabled() and pg.input_value('#fit-brand')!='')
    pg.fill('#fit-notes','Caliper variant'); pg.click('button:has-text("Save Note")'); pg.wait_for_load_state('networkidle')
    ok('note saved','Caliper variant' in pg.inner_text('body'))
    # quick add selector
    pg.fill('#quick-part','brake'); pg.wait_for_timeout(700)
    ok('product selector lists matches',pg.locator('ul button:has-text("Brake Pad")').count()==1)
    pg.locator('ul button:has-text("Brake Pad")').click(); pg.wait_for_timeout(300)
    ok('picking a product opens its fitment form',modal.is_visible() and 'Brake Pad' in pg.inner_text('[role=dialog]'))
    pg.keyboard.press('Escape')
    pg.fill('#quick-part','zzzzqq'); pg.wait_for_timeout(700)
    ok('empty selector shows message','No product matches' in pg.inner_text('body'))
    ok('no console errors across the whole owner session',not errs,str(errs))
    ctx2=br.new_context(viewport={'width':1400,'height':1000}); g=ctx2.new_page(); gerrs=[]
    g.on('pageerror',lambda e:gerrs.append(str(e)[:150])); g.on('console',lambda m:gerrs.append(m.text[:150]) if (m.type=='error' and 'Failed to load resource' not in m.text and 'Content Security' not in m.text) else None)
    g.goto(B+'/compatibility'); g.wait_for_load_state('networkidle'); g.wait_for_timeout(600)
    ms=g.locator('#modelSelect')
    ok('customer: model disabled until brand chosen',ms.is_disabled() and 'Select a brand first' in ms.inner_text())
    ok('customer: year filter hidden (no year-specific fitment)','hidden' in g.evaluate("document.getElementById('yearWrap').className"))
    ok('customer: brands list = Honda, Yamaha',g.locator('#makeSelect option').all_inner_texts()[1:]==['honda','Yamaha'] or g.locator('#makeSelect option').all_inner_texts()[1:]==['Honda','Yamaha'] ,str(g.locator('#makeSelect option').all_inner_texts()))
    g.select_option('#makeSelect',index=1); g.wait_for_timeout(600)
    ok('customer: models enabled after brand',not ms.is_disabled() and 'TMX 125' in ms.inner_text() and 'Sniper' not in ms.inner_text(),ms.inner_text())
    ok('customer: brand-only shows confirmed parts','Confirmed compatible with' in g.inner_text('#resultsBox'),g.inner_text('#resultsBox')[:120])
    g.select_option('#modelSelect',label='TMX 155'); g.wait_for_timeout(900)
    t=g.inner_text('#resultsBox')
    ok('customer: brand+model shows part with confirmed fit','Air Filter' in t and 'Confirmed fit' in t,t[:150])
    g.fill('#partInput','Brake'); g.wait_for_timeout(900)
    ok('customer: brand+model+part narrows (no match message)','No confirmed compatible parts' in g.inner_text('#resultsBox'),g.inner_text('#resultsBox')[:120])
    g.select_option('#makeSelect',index=0); g.wait_for_timeout(600)
    ok('customer: clearing brand disables model again',ms.is_disabled())
    g.fill('#partInput','Brake'); g.wait_for_timeout(1000)
    t=g.inner_text('#resultsBox')
    ok('customer: part-only lists product with no compatibility claim','Brake Pad' in t and 'Confirmed fit' not in t and 'Pick a brand and model' in t,t[:160])
    ok('customer: no console errors',not gerrs,str(gerrs))
    # now add a year-specific fitment as owner; the Year filter must appear for customers
    pg.goto(B+'/compatibility/manage'); pg.wait_for_load_state('networkidle')
    pg.locator('button:has-text("Add Fitment")').nth(3).click(); pg.wait_for_timeout(200)
    pg.fill('#fit-brand','Yamaha'); pg.fill('#fit-model','Mio 125'); pg.fill('#fit-year','2018-2020')
    pg.click('button:has-text("Save Fitment")'); pg.wait_for_load_state('networkidle')
    g.goto(B+'/compatibility'); g.wait_for_load_state('networkidle'); g.wait_for_timeout(700)
    ok('customer: year filter appears once a fitment has a year','hidden' not in g.evaluate("document.getElementById('yearWrap').className"))
    g.select_option('#makeSelect',label='Yamaha'); g.wait_for_timeout(500); g.select_option('#modelSelect',label='Mio 125'); g.wait_for_timeout(600)
    ok('customer: year options expanded from range',g.locator('#yearSelect option').all_inner_texts()==['All Years','2020','2019','2018'],str(g.locator('#yearSelect option').all_inner_texts()))
    ok('final: no console errors owner+customer',not errs and not gerrs,str(errs+gerrs))
    br.close()
print(json.dumps({'passed':sum(1 for r in res if r[1]),'failed':[r[0] for r in res if not r[1]]}))
