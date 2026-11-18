import express from 'express';
import { pathToFileURL } from 'node:url';

export function createApp() {
    const app = express();

    app.use(express.json());

    const products = [
        { id: 'P001', nama: 'Laptop', harga: 7500000, stok: 10 },
        { id: 'P002', nama: 'Mouse', harga: 150000, stok: 20 },
        { id: 'P003', nama: 'Keyboard', harga: 300000, stok: 15 },
        { id: 'P004', nama: 'Monitor', harga: 2000000, stok: 8 },
        { id: 'P005', nama: 'Headset', harga: 450000, stok: 12 }
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
        const product = products.find((item) => item.id === req.params.id);

        if (!product) {
            return res.status(404).json({ message: 'Produk tidak ditemukan' });
        }

        return res.json(product);
    });

    app.post('/produk', (req, res) => {
        const { id, nama, harga, stok } = req.body ?? {};
        const numericPrice = Number(harga);
        const numericStock = Number(stok);

        if (
            typeof id !== 'string' || !id.trim() ||
            typeof nama !== 'string' || !nama.trim() ||
            !Number.isFinite(numericPrice) || numericPrice < 0 ||
            !Number.isInteger(numericStock) || numericStock < 0
        ) {
            return res.status(422).json({
                message: 'Field id, nama, harga, dan stok wajib diisi dengan format yang benar.'
            });
        }

        if (products.some((item) => item.id === id.trim())) {
            return res.status(409).json({ message: 'ID produk sudah digunakan.' });
        }

        const newProduct = {
            id: id.trim(),
            nama: nama.trim(),
            harga: numericPrice,
            stok: numericStock
        };

        products.push(newProduct);

        return res.status(201).json({
            message: 'Produk berhasil ditambahkan.',
            data: newProduct
        });
    });

    app.patch('/produk/:id', (req, res) => {
        const product = products.find((item) => item.id === req.params.id);

        if (!product) {
            return res.status(404).json({ message: 'Produk tidak ditemukan' });
        }

        const body = req.body ?? {};
        const allowedFields = ['nama', 'harga', 'stok'];
        const suppliedFields = Object.keys(body);

        if (
            suppliedFields.length === 0 ||
            suppliedFields.some((field) => !allowedFields.includes(field))
        ) {
            return res.status(422).json({
                message: 'Kirim minimal satu field yang valid: nama, harga, atau stok.'
            });
        }

        const updates = {};

        if (Object.hasOwn(body, 'nama')) {
            if (typeof body.nama !== 'string' || !body.nama.trim()) {
                return res.status(422).json({ message: 'Nama harus berupa teks dan tidak boleh kosong.' });
            }
            updates.nama = body.nama.trim();
        }

        if (Object.hasOwn(body, 'harga')) {
            const price = Number(body.harga);
            if (!Number.isFinite(price) || price < 0) {
                return res.status(422).json({ message: 'Harga harus berupa angka nol atau lebih.' });
            }
            updates.harga = price;
        }

        if (Object.hasOwn(body, 'stok')) {
            const stock = Number(body.stok);
            if (!Number.isInteger(stock) || stock < 0) {
                return res.status(422).json({ message: 'Stok harus berupa bilangan bulat nol atau lebih.' });
            }
            updates.stok = stock;
        }

        Object.assign(product, updates);

        return res.json({ message: 'Produk berhasil diubah.', data: product });
    });

    app.delete('/produk/:id', (req, res) => {
        const index = products.findIndex((item) => item.id === req.params.id);

        if (index === -1) {
            return res.status(404).json({ message: 'Produk tidak ditemukan' });
        }

        const [deletedProduct] = products.splice(index, 1);

        return res.json({
            message: 'Produk berhasil dihapus',
            data: deletedProduct
        });
    });

    return app;
}

const PORT = Number(process.env.PORT) || 3000;
const app = createApp();
const isDirectExecution = process.argv[1]
    && import.meta.url === pathToFileURL(process.argv[1]).href;

if (isDirectExecution) {
    app.listen(PORT, () => {
        console.log(`Server berjalan di http://localhost:${PORT}`);
    });
}
