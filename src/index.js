async function fetchShopData(){
    try{
        const response = await fetch('http://localhost:999/get');
        const data = await response.json();

        console.log('data', data);

    }catch(e){
        console.error(e);
    }
}
document.addEventListener('DOMContentLoaded', ()=>{
    fetchShopData();
})