const express = require('express');

const app = express();
const PORT = 3000;

app.use(express.json());

const products = [
    {
        id: "P001",
        nama: "Laptop",
        harga: 7500000,
        stok: 10
    },
    {
        id: "P002",
        nama: "Mouse",
        harga: 150000,
        stok: 20
    },
    {
        id: "P003",
        nama: "Keyboard",
        harga: 300000,
        stok: 15
    },
    {
        id: "P004",
        nama: "Monitor",
        harga: 2000000,
        stok: 8
    },
    {
        id: "P005",
        nama: "Headset",
        harga: 450000,
        stok: 12
    }
];

app.get('/', (req, res) => {
    res.json({
        message: 'HTTP Server Praktikum Interoperabilitas berhasil berjalan'
    });
});

app.get('/produk', (req, res) => {
    res.json(products);
});

app.get('/produk/:id', (req, res) => {
    const product = products.find(p => p.id === req.params.id);

    if (!product) {
        return res.status(404).json({
            message: 'Produk tidak ditemukan'
        });
    }

    res.json(product);
});

app.post('/produk', (req, res) => {
    const newProduct = req.body;

    products.push(newProduct);

    res.status(201).json(newProduct);
});

app.patch('/produk/:id', (req, res) => {
    const product = products.find(p => p.id === req.params.id);

    if (!product) {
        return res.status(404).json({
            message: 'Produk tidak ditemukan'
        });
    }

    Object.assign(product, req.body);

    res.json(product);
});

app.delete('/produk/:id', (req, res) => {
    const index = products.findIndex(p => p.id === req.params.id);

    if (index === -1) {
        return res.status(404).json({
            message: 'Produk tidak ditemukan'
        });
    }

    const deletedProduct = products.splice(index, 1);

    res.json({
        message: 'Produk berhasil dihapus',
        data: deletedProduct[0]
    });
});

app.listen(PORT, () => {
    console.log(`Server berjalan di http://localhost:${PORT}`);
});