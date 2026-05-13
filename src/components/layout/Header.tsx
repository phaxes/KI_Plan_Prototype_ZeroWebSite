import { Link, useLocation } from 'react-router-dom';
import { LayoutGrid, ShoppingBag, BookOpen, User, Kanban as KanbanIcon } from 'lucide-react';
import { cn } from '../../lib/utils';

export default function Header() {
  const location = useLocation();
  
  const navItems = [
    { name: 'Home', path: '/', icon: LayoutGrid },
    { name: 'Blog', path: '/blog', icon: BookOpen },
    { name: 'Shop', path: '/shop', icon: ShoppingBag },
    { name: 'Auth', path: '/auth', icon: User },
    { name: 'Plan', path: '/kanban', icon: KanbanIcon },
  ];

  return (
    <header className="sticky top-0 z-50 w-full border-b-2 border-slate-900 bg-slate-50/90 backdrop-blur-sm px-4 lg:px-8">
      <div className="container mx-auto h-20 flex items-end justify-between pb-4">
        <Link to="/" className="flex flex-col">
          <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 leading-none mb-1">Professional Platform</span>
          <span className="font-black text-2xl tracking-tighter text-slate-900 uppercase leading-none">ProPresence</span>
        </Link>
        
        <nav className="hidden md:flex items-center gap-8">
          {navItems.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={cn(
                "text-[11px] font-black uppercase tracking-widest transition-all",
                location.pathname === item.path 
                  ? "text-cyan-500 border-b-2 border-cyan-500 pb-1" 
                  : "text-slate-400 hover:text-slate-900"
              )}
            >
              {item.name}
            </Link>
          ))}
        </nav>
        
        <div className="flex items-center gap-6">
           <div className="hidden lg:block text-right">
             <p className="text-[9px] font-bold uppercase text-slate-400 leading-none mb-1">Budget Limit</p>
             <p className="text-sm font-black leading-none">€2.00 / MONTH</p>
           </div>
           <button className="px-6 py-2 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest hover:bg-cyan-500 transition-colors">
             Launch App
           </button>
        </div>
      </div>
    </header>
  );
}
