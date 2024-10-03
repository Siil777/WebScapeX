let productList = [];

async function fetchShopData(){
    try{
        const response = await fetch('http://localhost:999/get');
        const data = await response.json();
        console.log('goods', data);

        const Elements = Object.keys(data);
        productList = data[Elements];
        UserI();

    }catch(e){
        console.error(e);
    }
}
function UserI(Goods = productList){
    const container = document.getElementById('container');
    container.classList.add('d-flex','justify-content-center','mt-5');
    container.innerText = '';
    const topDiv = document.createElement('div');
    topDiv.classList.add('list-group')
    Goods.forEach((good)=>{
        const goodNameDiv = document.createElement('a');
        goodNameDiv.href = "javascript:void(0);";
        goodNameDiv.classList.add('list-group-item','list-group-item-action');
        goodNameDiv.textContent = `Product type: ${good.name}`;
        topDiv.appendChild(goodNameDiv);
    });
    container.appendChild(topDiv);

}
document.addEventListener('DOMContentLoaded', ()=>{
    fetchShopData();
})