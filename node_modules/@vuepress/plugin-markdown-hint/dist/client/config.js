import './config.css';
import{useEventListener as e}from"@vueuse/core";import{defineClientConfig as t}from"vuepress/client";var n=t({setup(){e(`beforeprint`,()=>{document.querySelectorAll(`details`).forEach(e=>{e.open=!0})},{passive:!0})}});export{n as default};
//# sourceMappingURL=config.js.map