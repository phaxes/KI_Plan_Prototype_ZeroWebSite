import React, { useState } from 'react';
import { motion } from 'motion/react';
import { Search, Calendar, User, ArrowRight, Tag } from 'lucide-react';

interface BlogPost {
  id: string;
  title: string;
  excerpt: string;
  date: string;
  author: string;
  image: string;
  category: string;
}

const MOCK_POSTS: BlogPost[] = [
  {
    id: '1',
    title: 'Future of Sustainable Energy in Small Orgs',
    excerpt: 'How small organizations can lead the way in adopting renewable energy solutions without massive upfront capital.',
    date: 'May 10, 2026',
    author: 'Alex Rivera',
    image: 'https://images.unsplash.com/photo-1466611653911-95282fc3656d?auto=format&fit=crop&q=80&w=800',
    category: 'Sustainability'
  },
  {
    id: '2',
    title: 'Digital Transformation on a Budget',
    excerpt: 'Leveraging free-tier services like Firebase and Resend to build enterprise-grade applications for free.',
    date: 'May 08, 2026',
    author: 'Sarah Chen',
    image: 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=800',
    category: 'Technology'
  },
  {
    id: '3',
    title: 'Building Communities in 2026',
    excerpt: 'The psychological impact of digital-first community organizations and how to foster real engagement.',
    date: 'May 05, 2026',
    author: 'Marcus Jordan',
    image: 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&q=80&w=800',
    category: 'Community'
  }
];

export default function Blog() {
  const [searchTerm, setSearchTerm] = useState('');

  return (
    <div className="container mx-auto px-4 py-16">
      <div className="max-w-2xl mb-16">
        <h1 className="text-4xl md:text-5xl font-bold mb-6 tracking-tight">Insights & Stories</h1>
        <p className="text-xl text-neutral-500 leading-relaxed">
          Stay updated with the latest news, case studies, and technological breakthroughs.
        </p>
      </div>

      <div className="flex flex-col md:flex-row gap-4 mb-12">
        <div className="relative flex-grow">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" />
          <input
            type="text"
            placeholder="Search articles..."
            className="w-full pl-12 pr-4 py-4 rounded-2xl border border-neutral-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all bg-white"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        <div className="flex gap-2">
          {['All', 'Technology', 'Community', 'Sustainability'].map(cat => (
            <button key={cat} className="px-6 py-4 rounded-2xl bg-white border border-neutral-200 text-sm font-medium hover:bg-neutral-50 transition-all whitespace-nowrap">
              {cat}
            </button>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {MOCK_POSTS.map((post) => (
          <BlogCard key={post.id} post={post} />
        ))}
      </div>
    </div>
  );
}

function BlogCard({ post }: { key?: string, post: BlogPost }) {
  return (
    <motion.article 
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      className="group flex flex-col bg-white border border-slate-200 overflow-hidden hover:shadow-xl transition-all"
    >
      <div className="relative aspect-[16/10] overflow-hidden grayscale group-hover:grayscale-0 transition-all duration-500">
        <img 
          src={post.image} 
          alt={post.title}
          className="w-full h-full object-cover"
        />
        <div className="absolute top-0 left-0">
          <span className="bg-slate-900 px-3 py-1 text-white text-[10px] font-black uppercase tracking-widest border-r border-b border-slate-900">
            {post.category}
          </span>
        </div>
      </div>
      
      <div className="p-8 flex flex-col flex-grow">
        <div className="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">
          <div className="flex items-center gap-1.5">
            {post.date}
          </div>
          <div className="h-1 w-1 rounded-full bg-slate-300" />
          <div className="flex items-center gap-1.5">
            {post.author}
          </div>
        </div>
        
        <h3 className="text-xl font-black mb-4 leading-tight uppercase tracking-tight group-hover:text-cyan-600 transition-colors">
          {post.title}
        </h3>
        <p className="text-slate-500 text-sm font-medium mb-6 line-clamp-3 leading-relaxed">
          {post.excerpt}
        </p>
        
        <div className="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
           <span className="text-[10px] font-black uppercase tracking-widest group-hover:text-cyan-600">Read Article</span>
           <ArrowRight className="w-4 h-4 text-slate-300 group-hover:translate-x-1 group-hover:text-cyan-600 transition-all" />
        </div>
      </div>
    </motion.article>
  );
}
