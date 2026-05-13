import React from 'react';
import { motion } from 'motion/react';
import { ArrowRight, Zap, Shield, Globe, ShoppingCart, Mail, LayoutGrid } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Home() {
  return (
    <div className="overflow-hidden">
      {/* Hero Section */}
      <section className="relative pt-24 pb-32 border-b-2 border-slate-900 bg-white">
        <div className="container mx-auto px-4 lg:px-8 relative z-10">
          <div className="grid grid-cols-12 gap-8 items-center">
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
              className="col-span-12 lg:col-span-7"
            >
              <div className="flex items-center gap-4 mb-6">
                 <div className="h-0.5 w-12 bg-cyan-500" />
                 <span className="text-xs font-black uppercase tracking-[0.3em] text-slate-400">
                   Agency Disruption Suite
                 </span>
              </div>
              <h1 className="text-6xl md:text-8xl font-black tracking-tight text-slate-900 mb-8 leading-[0.9] uppercase">
                Zero Cost. <br />
                <span className="text-cyan-500">Max Scale.</span>
              </h1>
              <p className="text-lg font-medium text-slate-500 mb-10 leading-relaxed max-w-xl">
                A high-end web presence with CMS, CRM, and Shop functionality. 
                Sustainably built for small organizations with zero server overhead.
              </p>
              
              <div className="flex flex-col sm:flex-row items-center gap-4">
                <Link to="/kanban" className="w-full sm:w-auto px-10 py-5 bg-slate-900 text-white font-black uppercase text-xs tracking-widest hover:bg-cyan-500 transition-all">
                  Review Project Board
                </Link>
                <Link to="/shop" className="w-full sm:w-auto px-10 py-5 bg-white border-2 border-slate-900 text-slate-900 font-black uppercase text-xs tracking-widest hover:bg-slate-50 transition-all">
                  Marketplace Demo
                </Link>
              </div>
            </motion.div>
            
            <div className="hidden lg:grid col-span-5 grid-cols-2 gap-4">
               <div className="aspect-square bg-slate-100 border-2 border-slate-900"></div>
               <div className="aspect-square bg-cyan-500 border-2 border-slate-900"></div>
               <div className="aspect-square bg-slate-900 border-2 border-slate-900"></div>
               <div className="aspect-square bg-slate-50 border-2 border-slate-900 flex items-center justify-center">
                  <ArrowRight className="w-12 h-12 text-slate-300" />
               </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Grid */}
      <section className="py-24 bg-slate-50">
        <div className="container mx-auto px-4 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
            <div className="max-w-2xl">
              <h2 className="text-4xl font-black mb-4 uppercase">Modular Infrastructure</h2>
              <p className="text-slate-500 font-medium">
                Our architecture focuses on the "Lean-Zero" principle.
              </p>
            </div>
            <div className="flex gap-2">
               <div className="w-12 h-1 bg-slate-900"></div>
               <div className="w-12 h-1 bg-slate-200"></div>
               <div className="w-12 h-1 bg-slate-200"></div>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-0 border-l border-t border-slate-200">
            <FeatureCard 
              icon={<Zap className="w-6 h-6" />}
              title="Astro.js + React"
              tag="Frontend"
              description="Static Site Generation combined with React islands for maximum performance and SEO."
            />
            <FeatureCard 
              icon={<Shield className="w-6 h-6" />}
              title="Firebase Identity"
              tag="Security"
              description="Enterprise-grade auth via Google Cloud identity platform in the free tier."
            />
            <FeatureCard 
              icon={<ShoppingCart className="w-6 h-6" />}
              title="Local Basket"
              tag="Ecommerce"
              description="Client-side cart logic that persists without a heavy database backend."
            />
            <FeatureCard 
              icon={<Mail className="w-6 h-6" />}
              title="Mail API Sync"
              tag="CRM"
              description="Seamless Resend/Brevo integration for newsletters and transactional emails."
            />
            <FeatureCard 
              icon={<Globe className="w-6 h-6" />}
              title="Edge CDN"
              tag="Hosting"
              description="Global distribution via Firebase Hosting with zero configuration and cost."
            />
            <FeatureCard 
              icon={<LayoutGrid className="w-6 h-6" />}
              title="Firestore CMS"
              tag="Content"
              description="A custom dashboard allowing authors to publish news directly to the edge."
            />
          </div>
        </div>
      </section>
    </div>
  );
}

function FeatureCard({ icon, title, description, tag }: { icon: React.ReactNode, title: string, description: string, tag: string }) {
  return (
    <motion.div 
      whileHover={{ backgroundColor: '#fff' }}
      className="p-10 border-r border-b border-slate-200 bg-transparent transition-all group"
    >
      <p className="text-[10px] font-black uppercase tracking-widest text-cyan-600 mb-4">{tag}</p>
      <div className="w-12 h-12 bg-slate-900 flex items-center justify-center text-white mb-6 group-hover:bg-cyan-500 transition-colors">
        {icon}
      </div>
      <h3 className="text-xl font-black mb-3 uppercase">{title}</h3>
      <p className="text-slate-500 text-sm font-medium leading-relaxed">
        {description}
      </p>
    </motion.div>
  );
}
