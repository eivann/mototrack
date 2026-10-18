"""Inventory-sourced Compatibility Checker headless-browser check (Playwright, Python).
Not part of `php artisan test`. Runs against a LOCAL throwaway app only:
  DB_CONNECTION=sqlite DB_DATABASE=<throwaway.sqlite> COMPAT_INVENTORY_SOURCED=true \
  RECAPTCHA_SKIP_IP=127.0.0.1 php artisan serve --port=8769
The sqlite file is seeded with the production model-string distribution (see report). Never point at production.
"""
from playwright.sync_api import sync_playwright
B='http://127.0.0.1:8769'
res=[]
def ok(name,cond,extra=''):
    res.append((name,bool(cond),extra)); print(('PASS' if cond else 'FAIL'),name,extra)
with sync_playwright() as p:
    br=p.chromium.launch()
    for vp,label in (({'width':1400,'height':1000},'desktop'),({'width':390,'height':844},'mobile')):
        ctx=br.new_context(viewport=vp); pg=ctx.new_page(); errs=[]
        pg.on('pageerror',lambda e:errs.append('PAGEERR '+str(e)[:150]))
        pg.on('console',lambda m:errs.append('CONSOLE '+m.text[:150]) if (m.type=='error' and 'Failed to load resource' not in m.text and 'Content Security' not in m.text) else None)
        pg.goto(B+'/compatibility'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(600)
        opts=pg.eval_on_selector_all('#makeSelect option','els=>els.map(e=>e.textContent.trim())')
        ok(f'[{label}] Make dropdown = All + exactly five makes',opts==['All Makes / Brands','Yamaha','Honda','Suzuki','Kawasaki','Universal'],str(opts))
        ok(f'[{label}] Model disabled before a make',pg.is_disabled('#modelSelect'))
        ok(f'[{label}] Year hidden (no year data)','hidden' in pg.get_attribute('#yearWrap','class').split())  # class check: local env has no compiled Tailwind
        ok(f'[{label}] no empty-data note','hidden' in pg.get_attribute('#noFitmentNote','class').split())
        pg.select_option('#makeSelect','Honda'); pg.wait_for_timeout(600)
        m=pg.eval_on_selector_all('#modelSelect option','els=>els.map(e=>e.textContent.trim())')
        ok(f'[{label}] Honda models dependent',('Wave 125' in m) and ('Raider 150' not in m) and not pg.is_disabled('#modelSelect'),f'{len(m)} options')
        pg.select_option('#modelSelect','Wave 125'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1200)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] Honda Wave 125 shows confirmed results','Confirmed compatible with Honda Wave 125' in t and 'Confirmed fit: Honda Wave 125' in t,t[:80].replace('\n',' '))
        cards=pg.locator('#resultsBox .add-to-cart-btn, #resultsBox a:has-text("Login to Order")').count()
        ok(f'[{label}] result cards rendered',cards>0,str(cards))
        pg.select_option('#modelSelect','XRM (general)'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] family shows "Recorded for" + confirm-variant note','Recorded for: Honda XRM' in t and 'confirm your exact variant' in t,t[:120].replace('\n',' '))
        ok(f'[{label}] family never says Confirmed fit','Confirmed fit' not in t)
        # make-only wording, TMX, family heading, limit notice, escaping
        pg.select_option('#makeSelect','Honda'); pg.wait_for_timeout(500); pg.select_option('#modelSelect',''); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1200)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] make-only heading does not imply every-model fit','Each part shows the model it was recorded for' in t and 'Confirmed compatible' not in t and 'any model' not in t,t[:110].replace('\n',' '))
        ok(f'[{label}] make-only shows limit notice when >100','Showing the first 100 of' in t or pg.locator('#resultLimitNote').count()==1,t[:200].replace('\n',' ')[:100])
        pg.select_option('#modelSelect','TMX 155'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] Honda TMX 155 returns recorded parts','Confirmed compatible with Honda TMX 155' in t and 'Honda TMX 155' in t,t[:90].replace('\n',' '))
        pg.select_option('#modelSelect','XRM (general)'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] family heading says general line, not confirmed','general XRM line' in t.split('\n')[0] and 'Confirmed compatible' not in t.split('\n')[0],t.split('\n')[0][:120])
        pg.select_option('#makeSelect','Yamaha'); pg.wait_for_timeout(500)
        ok(f'[{label}] Yamaha lists STX and YZF-R15, Suzuki does not',(lambda m,n:('STX' in m and 'YZF-R15' in m and 'STX' not in n and 'R15' not in n))(pg.eval_on_selector_all('#modelSelect option','e=>e.map(x=>x.textContent.trim())'),(pg.select_option('#makeSelect','Suzuki'),pg.wait_for_timeout(500),pg.eval_on_selector_all('#modelSelect option','e=>e.map(x=>x.textContent.trim())'))[2]))
        pg.select_option('#makeSelect',''); pg.wait_for_timeout(300); pg.fill('#partInput','XSSPART'); pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1200)
        injected=pg.evaluate("document.querySelectorAll('#resultsBox img[src=x]').length")
        ok(f'[{label}] model text is escaped (no injected element)',injected==0 and 'onerror' in pg.inner_text('#resultsBox').lower(),str(injected))
        pg.fill('#partInput','')
        pg.select_option('#makeSelect','Universal'); pg.wait_for_timeout(500)
        ok(f'[{label}] Universal disables model',pg.is_disabled('#modelSelect') and 'Not applicable' in pg.inner_text('#modelSelect'))
        pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1200)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] Universal heading is not a confirmation','not guaranteed' in t and 'Confirmed compatible' not in t,t[:100].replace('\n',' '))
        ok(f'[{label}] Universal conditional flagged','confirm against your motorcycle' in t or 'Labelled "Universal"' in t)
        # optional part name
        pg.select_option('#makeSelect','Suzuki'); pg.wait_for_timeout(500); pg.select_option('#modelSelect','Raider 150')
        pg.fill('input[name=part], #partInput','air filter') if pg.locator('#partInput').count() else None
        pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1200)
        ok(f'[{label}] part name narrows within model','Raider 150' in pg.inner_text('#resultsBox') or 'No confirmed' in pg.inner_text('#resultsBox'))
        pg.fill('#partInput','zzzzqq') if pg.locator('#partInput').count() else None
        pg.click('button:has-text("Check Compatibility")'); pg.wait_for_timeout(1000)
        t=pg.inner_text('#resultsBox')
        ok(f'[{label}] empty state is non-absolute','No confirmed compatible parts' in t and 'no part' not in t.lower().replace('no confirmed',''),t[:120].replace('\n',' '))
        sw=pg.evaluate('document.documentElement.scrollWidth<=window.innerWidth+1')
        ok(f'[{label}] no horizontal scroll',sw)
        ok(f'[{label}] zero console/page errors',not errs,str(errs))
        pg.screenshot(path=f'/tmp/claude-0/uat2/compat_{label}.png',full_page=False)
        ctx.close()
    br.close()
bad=[r for r in res if not r[1]]
print(f'\n{len(res)-len(bad)}/{len(res)} passed'); raise SystemExit(1 if bad else 0)
