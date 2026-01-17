import{i}from"#entry";function r(e,s,o){const t=e.findIndex(n=>i(n,s)),d=e.findIndex(n=>i(n,o));if(t===-1||d===-1)return[];const[x,f]=[t,d].sort((n,I)=>n-I);return e.slice(x,f+1)}export{r as f};
