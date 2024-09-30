const express = require('express');
const path = require('path');
const fetch = require('node-fetch');
const cors = require('cors');
const port = 999;

const app = express();
app.use(cors());
app.use(express.urlencoded({extended: true}));
app.use(express.json());
//app.use(express.static(path.join(__dirname, 'build')));
app.get('/get', async (req,res)=>{
    try{
        const response = await fetch('http://localhost:8000/api.php');
        const data = await response.json();
        res.json(data);
    }catch(e){
        console.error(e);
        res.status(500).json({error: 'Field to fetch data'});
    }
});
app.listen(port, console.log(`app running at port ${port}`));