import React, { useState } from 'react';
import { motion, Reorder } from 'motion/react';
import { Layout, CheckCircle2, Clock, PlayCircle, Plus } from 'lucide-react';
import { cn } from '../lib/utils';

type Tag = 'Must' | 'Should' | 'Could';

interface PBI {
  id: string;
  title: string;
  description: string;
  status: 'Backlog' | 'In Progress' | 'Done';
  priority: Tag;
}

const INITIAL_PBIS: PBI[] = [
  { id: '1', title: 'PBI-01: Responsive Design', description: 'High-end landing page layout with mobile support.', status: 'Done', priority: 'Must' },
  { id: '2', title: 'PBI-02: CMS Blog', description: 'Firestore integration for articles and news.', status: 'In Progress', priority: 'Must' },
  { id: '3', title: 'PBI-03: User Auth', description: 'Firebase Auth register/login flow.', status: 'Backlog', priority: 'Must' },
  { id: '4', title: 'PBI-04: Simple Cart', description: 'Local storage based product cart system.', status: 'Backlog', priority: 'Must' },
  { id: '5', title: 'PBI-05: Buy Simulation', description: 'Success state after "buying" a product.', status: 'Backlog', priority: 'Must' },
  { id: '6', title: 'PBI-06: Newsletter API', description: 'Integration with Resend for emails.', status: 'Backlog', priority: 'Should' },
  { id: '7', title: 'PBI-07: Admin Panel', description: 'Simple GUI for content creators.', status: 'Backlog', priority: 'Should' },
  { id: '8', title: 'PBI-08: AI Content', description: 'Gemini-powered blog post generation.', status: 'Backlog', priority: 'Could' },
];

export default function Kanban() {
  const [items, setItems] = useState(INITIAL_PBIS);

  const columns = [
    { title: 'Backlog', color: 'bg-slate-300', status: 'Backlog' },
    { title: 'Processing', color: 'bg-yellow-400', status: 'In Progress' },
    { title: 'Done', color: 'bg-green-500', status: 'Done' },
  ];

  return (
    <div className="container mx-auto px-4 lg:px-8 py-16">
      <header className="flex justify-between items-end border-b-2 border-slate-900 pb-6 mb-12">
        <div>
          <p className="text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Project Tracker</p>
          <h1 className="text-4xl md:text-5xl font-black text-slate-900 uppercase tracking-tighter">Product Backlog</h1>
        </div>
        <div className="flex gap-12 text-right hidden md:flex">
          <div>
            <p className="text-[10px] font-bold uppercase text-slate-400">Total Tasks</p>
            <p className="text-2xl font-black">{items.length}</p>
          </div>
          <div>
            <p className="text-[10px] font-bold uppercase text-slate-400">Completion</p>
            <p className="text-2xl font-black text-green-600">
              {Math.round((items.filter(i => i.status === 'Done').length / items.length) * 100)}%
            </p>
          </div>
        </div>
      </header>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {columns.map((column) => (
          <div key={column.title} className="flex flex-col gap-6">
            <div className="flex items-center gap-3 border-b border-slate-200 pb-4">
              <div className={cn("w-3 h-3", column.color)}></div>
              <h4 className="text-xs font-black uppercase tracking-widest text-slate-900">{column.title}</h4>
            </div>

            <div className="flex flex-col gap-4">
              {items.filter(i => i.status === column.status).map((item) => (
                <KanbanItem key={item.id} item={item} />
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function KanbanItem({ item }: { key?: string, item: PBI }) {
  const priorityColors = {
    'Must': 'text-red-600',
    'Should': 'text-amber-600',
    'Could': 'text-cyan-600',
  };

  return (
    <motion.div
      layoutId={item.id}
      className="bg-white border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all relative overflow-hidden"
    >
      <div className="flex justify-between items-start mb-3">
        <span className={cn(
          "text-[9px] font-black uppercase tracking-[0.2em]",
          priorityColors[item.priority]
        )}>
          {item.priority}
        </span>
      </div>
      <h3 className="text-sm font-black text-slate-900 leading-tight mb-2 uppercase tracking-tight">{item.title}</h3>
      <p className="text-[11px] text-slate-500 font-medium leading-relaxed mb-4">
        {item.description}
      </p>
      
      <div className="flex justify-between items-center pt-4 border-t border-slate-50">
        <div className="flex gap-1">
          <span className="text-[8px] bg-slate-100 px-1 border border-slate-200 font-bold uppercase tracking-tighter">P{item.id}</span>
        </div>
        <div className="text-[9px] font-black uppercase tracking-widest text-slate-300">
          Sync Active
        </div>
      </div>
    </motion.div>
  );
}
