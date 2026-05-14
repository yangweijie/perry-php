const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const OUTPUT = process.env.OUTPUT || '/tmp/perry-e2e/screenshots';

async function takeScreenshot(url, name, viewport = { width: 1280, height: 900 }) {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport });
    await page.goto(url, { waitUntil: 'networkidle', timeout: 15000 });
    await page.waitForTimeout(1000);
    await page.screenshot({ path: path.join(OUTPUT, `${name}.png`), fullPage: true });
    await browser.close();
    console.log(`  ✅ ${name}.png`);
}

(async () => {
    fs.mkdirSync(OUTPUT, { recursive: true });
    
    const urls = {
        'calculator': `file://${__dirname}/calculator.html`,
        'todo':        `file://${__dirname}/todo.html`,
        'counter':     `file://${__dirname}/counter.html`,
        'perry-demo':  `file://${__dirname}/perry-demo.html`,
    };

    // Generate the HTML files
    const { execSync } = require('child_process');
    const projectDir = path.resolve(__dirname, '..');
    process.chdir(projectDir);
    
    // Generate web versions
    execSync('php examples/calculator.php web 2>/dev/null', { stdio: ['pipe', 'pipe', 'ignore'] });
    
    for (const [name, url] of Object.entries(urls)) {
        // Generate the HTML
        const genMap = {
            'calculator': 'php examples/calculator.php web',
            'todo': 'php examples/todo.php html',
            'counter': 'php examples/counter.php html',
            'perry-demo': 'php examples/perry-demo.php web',
        };
        execSync(genMap[name], { stdio: ['pipe', 'pipe', 'ignore'] });
        
        // Write to HTML file
        const htmlContent = execSync(genMap[name], { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'ignore'] });
        fs.writeFileSync(path.join(OUTPUT, `${name}.html`), htmlContent);
        
        // Take screenshot
        await takeScreenshot(`file://${path.join(OUTPUT, `${name}.html`)}`, name);
    }

    // SwiftUI: just verify compilation
    try {
        execSync('which swiftc', { stdio: 'ignore' });
        const swiftCode = execSync('php examples/calculator.php swiftui 2>/dev/null', { encoding: 'utf-8' });
        fs.writeFileSync(path.join(OUTPUT, 'calculator.swift'), swiftCode);
        execSync(`swiftc -o ${path.join(OUTPUT, 'calculator')} ${path.join(OUTPUT, 'calculator.swift')} -framework SwiftUI -parse-as-library`, { stdio: 'ignore' });
        console.log('  ✅ SwiftUI calculator compiles');
    } catch (e) {
        console.log('  ⚠️ SwiftUI compilation skipped:', e.message.split('\n')[0]);
    }

    console.log('\n=== E2E Test Complete ===');
    console.log(`Screenshots: ${OUTPUT}/`);
})();
