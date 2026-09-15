import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import {execFileSync} from 'node:child_process';
let scripts=0, php=0;
for(const dir of ['assets/js','student','teacher','.']){
    for(const name of fs.readdirSync(dir)){
        if(!/\.(js|html)$/.test(name)) continue;
        const file=path.join(dir,name),source=fs.readFileSync(file,'utf8');
        const blocks=name.endsWith('.js')?[source]:[...source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)].map(m=>m[1]).filter(s=>s.trim());
        for(const code of blocks){new vm.Script(code,{filename:file});scripts++;}
        const markup=source.replace(/(<script\b[^>]*>)[\s\S]*?<\/script>/gi,'$1</script>');
        if(name.endsWith('.html'))for(const match of markup.matchAll(/(?:src|href)="([^"#?]+)(?:[?#][^"]*)?"/g)){
            const ref=match[1];if(/^(?:[a-z]+:|\/\/|\$)/i.test(ref)||ref.includes('${'))continue;
            if(ref.startsWith('/'))continue;
            if(!fs.existsSync(path.resolve(dir,ref)))throw new Error(`Missing local resource: ${file} -> ${ref}`);
        }
    }
}
function lint(dir){for(const entry of fs.readdirSync(dir,{withFileTypes:true})){const file=path.join(dir,entry.name);if(entry.isDirectory())lint(file);else if(file.endsWith('.php')){execFileSync(process.env.PHP_BINARY||'C:/xampp/php/php.exe',['-l',file],{stdio:'pipe'});php++;}}}
for(const dir of ['api','database','tests'])lint(dir);
console.log(`Passed: ${scripts} JavaScript blocks/files, ${php} PHP files, and static local resource references.`);
