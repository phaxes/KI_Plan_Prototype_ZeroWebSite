import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { ShoppingCart, Star, Package, Check, X, ShieldCheck } from 'lucide-react';
import { cn } from '../lib/utils';

interface Product {
  id: string;
  name: string;
  price: number;
  description: string;
  image: string;
  category: string;
  rating: number;
}

const MOCK_PRODUCTS: Product[] = [
  {
    id: 'p1',
    name: 'Eco-Friendly Tech Organizer',
    price: 49.00,
    description: 'A professional organizer made from 100% recycled ocean plastic. Waterproof and durable.',
    image: 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?auto=format&fit=crop&q=80&w=800',
    category: 'Accessories',
    rating: 4.8
  },
  {
    id: 'p2',
    name: 'Minimalist Work Lamp',
    price: 89.00,
    description: 'Intelligent lighting with auto-adjust functionality and warm/cool spectrum switching.',
    image: 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&q=80&w=800',
    category: 'Workplace',
    rating: 4.9
  },
  {
    id: 'p3',
    name: 'Premium Glass Water Flask',
    price: 24.00,
    description: 'Double-walled borosilicate glass with a brushed steel lid. 750ml capacity.',
    image: 'https://images.unsplash.com/photo-1602143399827-bd95ef6f429c?auto=format&fit=crop&q=80&w=800',
    category: 'Lifestyle',
    rating: 4.7
  }
];

export default function Shop() {
  const [cart, setCart] = useState<Product[]>([]);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);

  const addToCart = (product: Product) => {
    setCart([...cart, product]);
  };

  const removeFromCart = (id: string) => {
    setCart(cart.filter(item => item.id !== id));
  };

  const handleCheckout = () => {
    setShowSuccess(true);
    setCart([]);
    setTimeout(() => setShowSuccess(false), 3000);
  };

  const total = cart.reduce((acc, item) => acc + item.price, 0);

  return (
    <div className="container mx-auto px-4 py-16">
      <div className="flex flex-col md:flex-row justify-between items-start mb-16 gap-8">
        <div className="max-w-2xl">
          <h1 className="text-4xl md:text-5xl font-bold mb-4 tracking-tight">Supporter Shop</h1>
          <p className="text-xl text-neutral-500">
            High-quality essentials for our community members. All proceeds support our projects.
          </p>
        </div>
        <button 
          onClick={() => setIsCartOpen(true)}
          className="relative px-6 py-4 bg-white border border-neutral-200 rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center gap-3"
        >
          <ShoppingCart className="w-5 h-5 text-blue-600" />
          <span className="font-bold">Cart</span>
          {cart.length > 0 && (
            <span className="absolute -top-2 -right-2 w-6 h-6 bg-blue-600 text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white animate-in zoom-in">
              {cart.length}
            </span>
          )}
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {MOCK_PRODUCTS.map((product) => (
          <ProductCard key={product.id} product={product} onAdd={() => addToCart(product)} />
        ))}
      </div>

      {/* Cart Modal */}
      <AnimatePresence>
        {isCartOpen && (
          <>
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsCartOpen(false)}
              className="fixed inset-0 bg-neutral-900/40 backdrop-blur-sm z-[60]"
            />
            <motion.div 
              initial={{ x: '100%' }}
              animate={{ x: 0 }}
              exit={{ x: '100%' }}
              transition={{ type: 'spring', damping: 25, stiffness: 200 }}
              className="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-[70] p-8 flex flex-col"
            >
              <div className="flex items-center justify-between mb-8">
                <h2 className="text-2xl font-bold">Your Selection</h2>
                <button onClick={() => setIsCartOpen(false)} className="p-2 hover:bg-neutral-100 rounded-full">
                  <X className="w-6 h-6 text-neutral-400" />
                </button>
              </div>

              <div className="flex-grow overflow-y-auto space-y-6">
                {cart.length === 0 ? (
                  <div className="text-center py-20 text-neutral-400">
                    <Package className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Your cart is empty.</p>
                  </div>
                ) : (
                  cart.map((item, idx) => (
                    <div key={`${item.id}-${idx}`} className="flex gap-4 p-4 rounded-2xl bg-neutral-50 border border-neutral-100">
                      <img src={item.image} className="w-20 h-20 rounded-xl object-cover" alt="" />
                      <div className="flex-grow">
                        <h4 className="font-bold text-sm mb-1">{item.name}</h4>
                        <p className="text-blue-600 font-bold">${item.price.toFixed(2)}</p>
                      </div>
                      <button onClick={() => removeFromCart(item.id)} className="text-neutral-400 hover:text-red-500 h-fit">
                        <X className="w-4 h-4" />
                      </button>
                    </div>
                  ))
                )}
              </div>

              <div className="mt-auto pt-8 border-t border-neutral-100">
                <div className="flex justify-between items-center mb-6">
                  <span className="text-neutral-500">Order Subtotal</span>
                  <span className="text-2xl font-bold">${total.toFixed(2)}</span>
                </div>
                <button 
                  disabled={cart.length === 0}
                  onClick={handleCheckout}
                  className="w-full py-5 bg-neutral-900 text-white rounded-2xl font-bold flex items-center justify-center gap-2 hover:bg-neutral-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  Confirm Purchase <ShieldCheck className="w-5 h-5" />
                </button>
                <p className="text-[10px] text-center text-neutral-400 mt-4 px-4 uppercase tracking-widest">
                  Secure transaction via local logic only for MVP purposes.
                </p>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>

      {/* Success Notification */}
      <AnimatePresence>
        {showSuccess && (
          <motion.div 
            initial={{ opacity: 0, y: 50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9 }}
            className="fixed bottom-10 left-1/2 -translate-x-1/2 px-6 py-4 bg-green-600 text-white rounded-2xl shadow-2xl flex items-center gap-3 z-[100]"
          >
            <Check className="w-5 h-5" />
            <span className="font-bold">Selection Confirmed! Thank you for your support.</span>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function ProductCard({ product, onAdd }: { key?: string, product: Product, onAdd: () => void }) {
  return (
    <motion.div 
      whileHover={{ y: -5 }}
      className="group bg-white border border-slate-200 p-6 transition-all hover:border-slate-900 hover:shadow-lg"
    >
      <div className="relative aspect-square overflow-hidden mb-6 bg-slate-100">
        <img 
          src={product.image} 
          alt={product.name}
          className="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-700"
        />
      </div>
      
      <div>
        <div className="flex justify-between items-start mb-4">
          <div>
            <span className="text-[10px] font-black text-cyan-600 uppercase tracking-widest mb-1 block">
              {product.category}
            </span>
            <h3 className="text-lg font-black tracking-tight line-clamp-1 uppercase leading-none">{product.name}</h3>
          </div>
          <span className="text-xl font-black text-slate-900">${product.price.toFixed(2)}</span>
        </div>
        
        <div className="flex items-center gap-1 mb-6">
           <Star className="w-3 h-3 text-slate-900 fill-slate-900" />
           <span className="text-[10px] font-black uppercase tracking-widest">{product.rating}</span>
        </div>
        
        <button 
          onClick={onAdd}
          className="w-full py-4 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest hover:bg-cyan-500 transition-all flex items-center justify-center gap-2"
        >
          Add Selection
        </button>
      </div>
    </motion.div>
  );
}

function PlusIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M8 3.33334V12.6667" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      <path d="M3.33334 8H12.6667" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
  );
}
