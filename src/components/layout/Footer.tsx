export default function Footer() {
  return (
    <footer className="border-t border-neutral-200 bg-white py-12">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div className="col-span-1 md:col-span-2">
            <div className="flex items-center gap-2 mb-4">
              <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
                P
              </div>
              <span className="font-bold text-xl tracking-tight">ProPresence</span>
            </div>
            <p className="text-neutral-500 max-w-sm">
              Professional high-end web presence solutions with zero-cost backend infrastructure.
              Perfect for small organizations and non-profits.
            </p>
          </div>
          
          <div>
            <h4 className="font-bold mb-4 text-sm uppercase tracking-widest text-neutral-400">Project</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/blog" className="text-neutral-600 hover:text-blue-600">Blog & News</a></li>
              <li><a href="/shop" className="text-neutral-600 hover:text-blue-600">Shop System</a></li>
              <li><a href="/kanban" className="text-neutral-600 hover:text-blue-600">Project Plan</a></li>
            </ul>
          </div>
          
          <div>
            <h4 className="font-bold mb-4 text-sm uppercase tracking-widest text-neutral-400">Legal</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="#" className="text-neutral-600 hover:text-blue-600">Privacy Policy</a></li>
              <li><a href="#" className="text-neutral-600 hover:text-blue-600">Terms of Service</a></li>
              <li><a href="#" className="text-neutral-600 hover:text-blue-600">Imprint</a></li>
            </ul>
          </div>
        </div>
        
        <div className="mt-12 pt-8 border-t border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-sm text-neutral-400">
            © {new Date().getFullYear()} ProPresence AI. Built for the AI-First Challenge.
          </p>
          <div className="flex gap-6">
            {/* Social links placeholder */}
          </div>
        </div>
      </div>
    </footer>
  );
}
