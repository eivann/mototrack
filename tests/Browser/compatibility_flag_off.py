"""Flag-OFF (Release B) headless check. Local throwaway app only:
COMPAT_INVENTORY_SOURCED=false php artisan serve --port=8770 against a sqlite file with one Honda TMX 155 fitment."""
from playwright.sync_api import sync_playwright
B='http://127.0.0.1:8770'; res=[]
def ok(n,c,x=''): res.append(bool(c)); print('PASS' if c else 'FAIL',n,x)
with sync_playwright() as p:
    br=p.chromium.launch(); pg=br.new_page(viewport={'width':1400,'height':1000}); errs=[]
    pg.on('pageerror',lambda e:errs.append(str(e)))
    pg.goto(B+'/compatibility'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(600)
    opts=pg.eval_on_selector_all('#makeSelect option','e=>e.map(x=>x.textContent.trim())')
    ok('makes come only from fitment records (no Universal / catalogue makes)',opts==['All Makes / Brands','Honda'],str(opts))
    pg.select_option('#makeSelect','Honda'); pg.wait_for_timeout(500)
    m=pg.eval_on_selector_all('#modelSelect option','e=>e.map(x=>x.textContent.trim())')
    ok('models only the recorded fitment model',m==['All models of this brand','TMX 155'],str(m))
    pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
    t=pg.inner_text('#resultsBox')
    ok('Release B make-only heading text unchanged','Confirmed compatible with Honda (any model)' in t,t[:80].replace('\n',' '))
    pg.select_option('#modelSelect','TMX 155'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
    t=pg.inner_text('#resultsBox')
    ok('Release B model heading unchanged','Confirmed compatible with Honda TMX 155' in t and 'Recorded for' not in t and 'Showing the first' not in t,t[:80].replace('\n',' '))
    ok('no console/page errors',not errs,str(errs)); br.close()
raise SystemExit(0 if all(res) else 1)
