<?php
   require_once 'Audit_Engine.php';
   
   $ledger = [];
   $error = null;
   $benford = null;
   $trustScore = 0;
   $statusColor = 'text-slate-500';
   
   // Handle File Upload
   if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['audit_file'])) {
       $file = $_FILES['audit_file'];
       $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
   
       if ($fileExtension !== 'json') {
           $error = "Invalid file type. Please upload a file ending in .json";
       } elseif ($file['error'] !== UPLOAD_ERR_OK) {
           $error = "Upload failed.";
       } else {
           $engine = new AuditEngine($file['tmp_name']);
           $ledger = $engine->runAudit();
           $systemErrors = $engine->getSystemErrors(); // Fetch the logs
           
           if (empty($ledger)) {
               $error = "File is empty or invalid JSON.";
           } else {
               // Success: Run Benford Analysis immediately while file is in memory
               $benford = $engine->getBenfordAnalysis();
               $totalDeviation = array_sum(array_column($benford, 'deviation'));
               // Old: $trustScore = max(0, 100 - ($totalDeviation * 2));
   // New: More balanced for smaller datasets
   $trustScore = max(0, 100 - ($totalDeviation * 0.8));
               $statusColor = $trustScore > 85 ? 'text-emerald-500' : ($trustScore > 60 ? 'text-orange-500' : 'text-red-500');
           }
       }
   }
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>Forensic Sentinel | Amit Sah</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
      <style>
         .dataTables_wrapper .dataTables_filter input { border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; color: #1e293b; }
         .dataTables_wrapper { color: #1e293b; }
         /* Preloader Overlay */
         #preloader {
         position: fixed;
         top: 0;
         left: 0;
         width: 100%;
         height: 100%;
         background-color: #0f172a; /* matches slate-950 */
         z-index: 9999;
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         transition: opacity 0.5s ease;
         }
         /* The Spinning Ring */
         .loader-ring {
         width: 60px;
         height: 60px;
         border: 4px solid rgba(99, 102, 241, 0.1);
         border-top: 4px solid #6366f1; /* Indigo-500 */
         border-radius: 50%;
         animation: spin 1s linear infinite;
         }
         @keyframes spin {
         0% { transform: rotate(0deg); }
         100% { transform: rotate(360deg); }
         }
         .loader-text {
         margin-top: 20px;
         font-size: 10px;
         font-weight: 900;
         text-transform: uppercase;
         letter-spacing: 0.2em;
         color: #6366f1;
         animation: pulse 1.5s infinite;
         }
         @keyframes pulse {
         0%, 100% { opacity: 1; }
         50% { opacity: 0.4; }
         }
         /* Add this to your 
      <style> section to make the AI output look crisp */
         .max-w-none b {
         color: #ffffff;
         font-weight: 700;
         }
         h5.text-indigo-400 {
         display: block;
         border-bottom: 1px solid rgba(99, 102, 241, 0.1);
         padding-bottom: 4px;
         }
      </style>
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   </head>
   <body class="bg-slate-900 py-12 px-4 font-sans text-slate-200">
      <nav class="bg-slate-900 border-b border-slate-800 py-3 px-6 flex justify-between items-center">
    <div class="flex items-center gap-4">
        <a href="https://projects.amitsah.com.np" class="text-slate-400 hover:text-white text-xs font-bold transition-colors">
            &larr; Return to Lab
        </a>
        <span class="text-slate-700">|</span>
        <span class="text-indigo-400 text-xs font-black uppercase tracking-widest">Forensic Engine v1.0.0</span>
    </div>
    <a href="https://amitsah.com.np" class="text-slate-500 hover:text-white text-[10px] uppercase tracking-tighter transition-colors">
        Amit Sah Portfolio
    </a>
</nav>
      <div id="preloader">
         <div class="loader-ring"></div>
         <div class="loader-text">Initializing Forensic Engine...</div>
      </div>
      <div class="max-w-7xl mx-auto">
         <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-end mb-8">
               <div>
                  <h1 class="text-4xl font-black text-white tracking-tighter">FORENSIC SENTINEL <span class="text-indigo-500">v1.0</span></h1>
                  <p class="text-slate-400 font-medium">Computational Auditing & Anomaly Detection Engine</p>
               </div>
               <div class="text-right">
                  <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Developer: Amit Sah, MBA</p>
                  <?php if (!empty($systemErrors)): ?>
                  <button onclick="document.getElementById('errorModal').classList.remove('hidden')" class="mt-2 text-[10px] bg-red-500/20 hover:bg-red-500/40 text-red-400 border border-red-500/50 px-3 py-1 rounded-full font-black uppercase tracking-tighter animate-pulse transition-all">
                  ⚠️ <?php echo count($systemErrors); ?> System Alerts
                  </button>
                  <?php endif; ?>
               </div>
            </div>
         </div>
         <div id="errorModal" class="hidden fixed inset-0 bg-slate-950/90 backdrop-blur-md z-[200] flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
               <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
                  <div class="flex items-center gap-2">
                     <div class="w-2 h-2 bg-red-500 rounded-full animate-ping"></div>
                     <h3 class="text-white font-bold uppercase tracking-tighter text-sm">System Execution Logs</h3>
                  </div>
                  <button onclick="document.getElementById('errorModal').classList.add('hidden')" class="text-slate-500 hover:text-white transition-colors">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                     </svg>
                  </button>
               </div>
               <div class="p-6 max-h-96 overflow-y-auto space-y-4 custom-scrollbar">
                  <?php foreach ($systemErrors as $err): ?>
                  <div class="flex gap-4 items-start p-4 rounded-2xl <?php echo $err['severity'] == 'critical' ? 'bg-red-500/10 border border-red-500/20' : 'bg-amber-500/10 border border-amber-500/20'; ?>">
                     <div class="flex flex-col items-center">
                        <span class="text-[9px] font-mono font-bold text-slate-500 uppercase"><?php echo $err['severity']; ?></span>
                        <span class="text-[10px] font-mono text-slate-400 mt-1"><?php echo $err['timestamp']; ?></span>
                     </div>
                     <p class="text-xs leading-relaxed <?php echo $err['severity'] == 'critical' ? 'text-red-200' : 'text-amber-200'; ?>">
                        <?php echo $err['message']; ?>
                     </p>
                  </div>
                  <?php endforeach; ?>
               </div>
               <div class="p-4 bg-slate-800/30 text-center">
                  <p class="text-[9px] text-slate-500 uppercase font-bold tracking-widest">Sentinel Diagnostic Output</p>
               </div>
            </div>
         </div>
         <div class="max-w-4xl mx-auto mt-8 mb-10 px-4">
    <div class="bg-indigo-950/30 border border-indigo-500/20 rounded-2xl p-6 shadow-xl">
        <h3 class="text-white font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-indigo-400"></i> How to use this Engine
        </h3>
        
        <div class="grid md:grid-cols-3 gap-6">
            <div class="space-y-2">
                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Step 01</span>
                <p class="text-slate-300 text-sm font-semibold">Download Sample Data</p>
                <a href="Mega_Data_Source.json" download class="inline-flex items-center gap-2 text-xs text-white bg-indigo-600 hover:bg-indigo-500 px-3 py-2 rounded-lg transition-all font-bold">
                    <i class="fas fa-download"></i> Get Sample JSON
                </a>
            </div>

            <div class="space-y-2">
                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Step 02</span>
                <p class="text-slate-300 text-sm font-semibold">Load the Ledger</p>
                <p class="text-slate-500 text-[11px] leading-tight">Drag the downloaded file into the "Dropzone" below or click to browse.</p>
            </div>

            <div class="space-y-2">
                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Step 03</span>
                <p class="text-slate-300 text-sm font-semibold">Review Audit AI</p>
                <p class="text-slate-500 text-[11px] leading-tight">The engine will calculate Weighted Risk Indices and generate executive reasoning.</p>
            </div>
        </div>
    </div>
</div>
         <div id="dropZone" class="group bg-slate-800 border-2 border-dashed border-slate-700 rounded-2xl p-12 mb-10 text-center hover:border-indigo-500 hover:bg-slate-800/50 transition-all cursor-pointer relative" onclick="document.getElementById('audit_file').click()">
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm" class="pointer-events-none">
               <div class="space-y-4">
                  <div class="bg-indigo-500/10 w-20 h-20 rounded-full flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                     <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                     </svg>
                  </div>
                  <div>
                     <h3 class="text-xl font-bold text-white tracking-tight">Upload Forensic Ledger</h3>
                     <p class="text-slate-400 text-sm mt-1">Drag and drop JSON file here</p>
                     <div class="mt-4 pointer-events-auto">
                        <a href="Mega_Data_Source.json" download class="text-[10px] bg-slate-700 hover:bg-indigo-600 text-slate-300 hover:text-white px-3 py-1.5 rounded-full border border-slate-600 transition-all font-bold uppercase tracking-widest inline-flex items-center gap-2">
                           <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                           </svg>
                           Download Sample Dataset (Mega_Data_Source.json)
                        </a>
                     </div>
                  </div>
                  <input type="file" name="audit_file" id="audit_file" class="hidden" onchange="triggerSentinelAnalysis()">
               </div>
            </form>
            <?php if ($error): ?> 
            <p class="mt-4 text-red-400 font-bold text-sm"><?php echo $error; ?></p>
            <?php endif; ?>
         </div>
         <?php if (!empty($ledger)): ?>
         <?php 
            $totalCount = count($ledger);
            $totalAlerts = 0; $totalValueAtRisk = 0;
            foreach ($ledger as $item) { if (!empty($item['alerts'])) { $totalAlerts++; $totalValueAtRisk += $item['amount']; } }
            ?>
         <?php
            // Prepare time-series data for the chart
            $timeData = [];
            foreach ($ledger as $tx) {
                $date = $tx['date'];
                if (!isset($timeData[$date])) {
                    $timeData[$date] = ['tx' => 0, 'alerts' => 0];
                }
                $timeData[$date]['tx']++;
                
                // FIXED: Change $row to $tx here
                if (!empty($tx['alerts'])) { 
                    $timeData[$date]['alerts']++;
                }
            }
            ksort($timeData); 
            
            $labels = json_encode(array_keys($timeData));
            $txCounts = json_encode(array_column($timeData, 'tx'));
            $alertCounts = json_encode(array_column($timeData, 'alerts'));
            ?>
         <?php
            $categoryRisk = [];
            foreach ($ledger as $tx) {
                $cat = $tx['category'];
                if (!isset($categoryRisk[$cat])) {
                    $categoryRisk[$cat] = ['total' => 0, 'alerts' => 0];
                }
                $categoryRisk[$cat]['total']++;
                if (!empty($tx['alerts'])) {
                    $categoryRisk[$cat]['alerts']++;
                }
            }
            
            // Sort categories so the highest risk appears at the top (Pareto Style)
            uasort($categoryRisk, function($a, $b) {
                return $b['alerts'] <=> $a['alerts'];
            });
            
            $catLabels = json_encode(array_keys($categoryRisk));
            $catAlertData = json_encode(array_column($categoryRisk, 'alerts'));
            $catTotalData = json_encode(array_column($categoryRisk, 'total'));
            ?>
         <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl">
               <p class="text-slate-400 text-xs font-bold uppercase tracking-widest text-center">Total Records</p>
               <h2 class="text-3xl font-black text-white text-center"><?php echo $totalCount; ?></h2>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl border-l-4 border-l-orange-500">
               <p class="text-slate-400 text-xs font-bold uppercase tracking-widest text-center">Anomalies</p>
               <h2 class="text-3xl font-black text-orange-500 text-center"><?php echo $totalAlerts; ?></h2>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl">
               <p class="text-slate-400 text-xs font-bold uppercase tracking-widest text-center">Value at Risk</p>
               <h2 class="text-3xl font-black text-white text-center">£<?php echo number_format($totalValueAtRisk, 2); ?></h2>
            </div>
         </div>
         <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl mb-10 shadow-xl">
            <div class="flex justify-between items-center mb-6">
               <div>
                  <h3 class="text-white font-bold text-lg leading-none">Temporal Velocity Analysis</h3>
                  <p class="text-slate-500 text-xs mt-2 italic font-medium">Daily volume distribution vs. anomaly detection spikes</p>
               </div>
               <div class="flex gap-6 text-[10px] uppercase font-black tracking-widest">
                  <span class="flex items-center gap-2 text-indigo-400"><span class="w-3 h-1 bg-indigo-500 rounded-full"></span> Volume</span>
                  <span class="flex items-center gap-2 text-orange-500"><span class="w-3 h-1 bg-orange-500 rounded-full"></span> Anomaly</span>
               </div>
            </div>
            <div class="h-72 w-full">
               <canvas id="temporalChart"></canvas>
            </div>
         </div>
         <?php if ($benford): ?>
         <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl mb-10">
            <div class="flex justify-between items-center mb-6">
               <div class="pb-8">
                  <h3 class="text-white font-bold text-lg">Benford's Law Analysis</h3>
                  <p class="text-slate-400 text-sm italic">Mathematical Integrity Score</p>
               </div>
               <div class="text-right">
                  <span class="text-[10px] text-slate-500 block uppercase font-bold tracking-tighter">Trust Score</span>
                  <span class="text-4xl font-black <?php echo $statusColor; ?>"><?php echo round($trustScore); ?>%</span>
               </div>
            </div>
            <div class="flex items-end gap-1 h-32 pt-4 border-b border-slate-700">
               <?php foreach ($benford as $digit => $stats): ?>
               <div class="flex-1 flex flex-col items-center group relative">
                  <div class="absolute -top-10 hidden group-hover:block bg-indigo-600 text-white text-[10px] p-2 rounded z-50">Digit <?php echo $digit; ?>: <?php echo $stats['actual']; ?>%</div>
                  <div class="w-full bg-indigo-500/20 rounded-t-sm relative" style="height: <?php echo $stats['expected'] * 3; ?>px;">
                     <div class="w-full bg-indigo-500 rounded-t-sm absolute bottom-0" style="height: <?php echo $stats['actual'] * 3; ?>px;"></div>
                  </div>
                  <span class="text-[9px] text-slate-500 mt-2"><?php echo $digit; ?></span>
               </div>
               <?php endforeach; ?>
            </div>
         </div>
         <?php endif; ?>
         <?php 
            $vendorProfiles = $engine->getVendorRiskProfiles(); 
            ?>
         <div class="mt-10 mb-20 bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl transition-all hover:border-indigo-500/50">
            <div class="px-8 py-4 bg-slate-900/80 border-b border-slate-700 flex justify-between items-center">
               <div class="flex items-center gap-3">
                  <div class="p-2 bg-indigo-500/10 rounded-lg border border-indigo-500/20">
                     <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                     </svg>
                  </div>
                  <h4 class="text-white font-black text-xs uppercase tracking-widest">Forensic AI Reasoning Engine</h4>
               </div>
               <div class="flex items-center gap-2">
                  <span class="text-[10px] font-bold text-slate-500 uppercase">Analysis Mode:</span>
                  <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-tighter bg-indigo-400/10 px-2 py-0.5 rounded border border-indigo-400/20">Heuristic Inference</span>
               </div>
            </div>
            <div class="p-8">
               <div class="max-w-none text-slate-300 space-y-6"> <?php 
                  $aiOutput = $engine->generateAIInsight($totalCount, $totalAlerts, $trustScore, $categoryRisk, $vendorProfiles);
                  
                  // 1. Clean up potential double-spacing issues
                  $aiOutput = trim($aiOutput);
                  
                  // 2. Process Headers (###)
                  $aiOutput = str_replace('###', '<h5 class="text-indigo-400 font-bold uppercase text-xs tracking-widest mt-6 mb-2 first:mt-0">', $aiOutput);
                  
                  // 3. Process Bolding (**) - Ensuring we close tags correctly
                  // We use a regex to ensure **text** becomes <b>text</b>
                  $aiOutput = preg_replace('/\*\*(.*?)\*\*/', '<b class="text-white font-bold">$1</b>', $aiOutput);
                  
                  // 4. Process Line Breaks
                  echo nl2br($aiOutput); 
                  ?></div>
               <div class="mt-8 pt-6 border-t border-slate-700 flex flex-wrap gap-8">
                  <div class="flex flex-col">
                     <span class="text-[9px] text-slate-500 uppercase font-black">Anomaly Gravity</span>
                     <span class="text-sm font-mono text-white"><?php echo round(($totalAlerts/$totalCount)*100, 1); ?>%</span>
                  </div>
                  <div class="flex flex-col">
                     <span class="text-[9px] text-slate-500 uppercase font-black">Category Vector</span>
                     <span class="text-sm font-mono text-orange-400"><?php echo $topCategory; ?></span>
                  </div>
                  <div class="flex flex-col">
                     <span class="text-[9px] text-slate-500 uppercase font-black">Trust Index</span>
                     <span class="text-sm font-mono <?php echo $trustScore > 75 ? 'text-emerald-400' : 'text-red-400'; ?>"><?php echo round($trustScore); ?>%</span>
                  </div>
               </div>
            </div>
         </div>
         <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <div class="bg-slate-800 border border-slate-700 p-8 rounded-2xl shadow-xl">
               <div class="mb-6">
                  <h3 class="text-white font-bold text-lg">Risk Concentration by Category</h3>
                  <p class="text-slate-400 text-xs italic">Pareto analysis identifying high-leakage departments.</p>
               </div>
               <div class="h-80">
                  <canvas id="categoryChart"></canvas>
               </div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-2xl overflow-hidden shadow-xl">
               <div class="p-6 border-b border-slate-700">
                  <h3 class="text-white font-bold text-lg">Vendor Risk Intelligence</h3>
                  <p class="text-slate-400 text-xs italic">Entity-level profiling and integrity gaps.</p>
               </div>
               <div class="overflow-x-auto">
                  <table class="w-full text-left">
                     <thead class="bg-slate-900/50 text-[10px] text-slate-500 uppercase font-bold tracking-widest">
                        <tr>
                           <th class="px-6 py-4">Vendor Entity</th>
                           <th class="px-6 py-4">Activity</th>
                           <th class="px-6 py-4">Risk Score</th>
                           <th class="px-6 py-4">Status</th>
                        </tr>
                     </thead>
                     <tbody class="divide-y divide-slate-700/50 text-sm">
                        <?php foreach (array_slice($vendorProfiles, 0, 5) as $vendor): ?>
                        <tr class="hover:bg-slate-700/30 transition-colors">
                           <td class="px-6 py-4 font-bold text-slate-200"><?php echo $vendor['name']; ?></td>
                           <td class="px-6 py-4 text-slate-400"><?php echo $vendor['tx_count']; ?> Txns (£<?php echo number_format($vendor['total_value']); ?>)</td>
                           <td class="px-6 py-4">
                              <div class="w-24 bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                 <div class="bg-indigo-500 h-full" style="width: <?php echo $vendor['risk_score']; ?>%"></div>
                              </div>
                              <span class="text-[10px] text-slate-500 mt-1"><?php echo $vendor['risk_score']; ?>% Integrity Gap</span>
                           </td>
                           <td class="px-6 py-4">
                              <?php 
                                 $color = $vendor['rating'] == 'High Risk' ? 'text-red-400 bg-red-400/10' : ($vendor['rating'] == 'Medium Risk' ? 'text-orange-400 bg-orange-400/10' : 'text-emerald-400 bg-emerald-400/10');
                                 ?>
                              <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $color; ?>">
                              <?php echo $vendor['rating']; ?>
                              </span>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
         <div class="bg-white rounded-2xl shadow-2xl overflow-hidden p-6 text-slate-800">
            <table id="auditTable" class="display w-full border-b border-slate-200">
               <thead>
                  <tr class="text-slate-500 text-[10px] uppercase font-bold">
                     <th class="px-4">Date</th>
                     <th class="text-center">Risk Index</th>
                     <th>Vendor</th>
                     <th>Category</th>
                     <th class="text-right">Amount</th>
                     <th class="text-right px-4">Risk Flags</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-slate-100">
                  <?php foreach ($ledger as $row): 
                     // Determine row background based on the Weighted Risk Index from Audit_Engine
                     $rowBg = 'hover:bg-slate-50';
                     if ($row['risk_index'] >= 60) {
                         $rowBg = 'bg-red-500/5 border-l-4 border-l-red-500';
                     } elseif ($row['risk_index'] >= 30) {
                         $rowBg = 'bg-orange-500/5 border-l-4 border-l-orange-500';
                     }
                     ?>
                  <tr class="<?php echo $rowBg; ?> transition-colors border-b border-slate-100">
                     <td class="py-4 px-4 text-sm font-medium"><?php echo $row['date']; ?></td>
                     <td class="py-4 text-center">
                        <div class="flex flex-col items-center">
                           <span class="text-[10px] font-bold text-slate-500"><?php echo $row['risk_index']; ?>%</span>
                           <div class="w-12 h-1.5 bg-slate-200 rounded-full overflow-hidden mt-1">
                              <?php 
                                 $barColor = $row['risk_index'] >= 60 ? 'bg-red-500' : ($row['risk_index'] >= 30 ? 'bg-orange-500' : 'bg-emerald-500');
                                 ?>
                              <div class="<?php echo $barColor; ?> h-full" style="width: <?php echo $row['risk_index']; ?>%"></div>
                           </div>
                        </div>
                     </td>
                     <td class="py-4 text-sm font-bold text-slate-700 px-2 overflow-visible">
                        <div class="flex items-center gap-2 relative group">
                           <span><?php echo $row['vendor']; ?></span>
                           <?php 
                              $vProfile = $vendorProfiles[$row['vendor']];
                              if ($vProfile['rating'] == 'High Risk'): ?>
                           <div class="cursor-help text-red-600">
                              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                 <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                              </svg>
                              <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-[100] w-56 bg-slate-900 text-white text-[10px] p-3 rounded-lg shadow-2xl border border-slate-700 pointer-events-none">
                                 <p class="font-black text-red-400 mb-1 uppercase tracking-widest flex items-center gap-1">
                                    <span>🚨</span> High Risk Entity
                                 </p>
                                 <p class="font-normal opacity-90 leading-relaxed">
                                    This vendor has a <span class="text-white font-bold"><?php echo $vProfile['risk_score']; ?>%</span> integrity gap across <span class="text-white font-bold"><?php echo $vProfile['tx_count']; ?></span> transactions.
                                 </p>
                                 <div class="w-2 h-2 bg-slate-900 rotate-45 absolute -bottom-1 left-3 border-r border-b border-slate-700"></div>
                              </div>
                           </div>
                           <?php endif; ?>
                        </div>
                     </td>
                     <td class="py-4 text-sm text-slate-500"><?php echo $row['category']; ?></td>
                     <td class="py-4 text-sm font-black text-right">£<?php echo number_format($row['amount'], 2); ?></td>
                     <td class="py-4 px-4 text-right">
                        <div class="flex justify-end">
                           <?php if (!empty($row['alerts'])): ?>
                           <div class="relative group cursor-help">
                              <span class="bg-orange-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black"><?php echo count($row['alerts']); ?> FLAGS</span>
                              <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block z-50 w-64 bg-slate-900 text-white text-[10px] p-3 rounded shadow-2xl border border-slate-700 text-left">
                                 <ul class="space-y-1">
                                    <?php foreach ($row['alerts'] as $alert): ?>
                                    <li class="flex items-center gap-2"><span class="w-1 h-1 bg-orange-500 rounded-full"></span><?php echo $alert; ?></li>
                                    <?php endforeach; ?>
                                 </ul>
                              </div>
                           </div>
                           <?php else: ?>
                           <span class="text-emerald-500 text-[10px] font-bold uppercase opacity-40 italic tracking-tighter">Clean</span>
                           <?php endif; ?>
                        </div>
                     </td>
                  </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>
         <?php endif; ?>
         <div class="mt-8 flex justify-between text-[10px] text-slate-500 font-bold uppercase tracking-widest">
            <p>© 2026 Amit Sah | York, UK</p>
         </div>
      </div>
      <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
      <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
      <script>
         $(document).ready(function() {
         
             // --- 1. THE SHARED UPLOAD TRIGGER ---
             // This handles the loader and submission for BOTH click and drop
             window.triggerSentinelAnalysis = function() {
                 const preloader = document.getElementById('preloader');
                 const fileInput = document.getElementById('audit_file');
                 const uploadForm = document.getElementById('uploadForm');
         
                 if (fileInput.files.length > 0) {
                     if (preloader) {
                         preloader.style.display = 'flex';
                         preloader.style.opacity = '1';
                         document.querySelector('.loader-text').innerText = "Analyzing Forensic Ledger...";
                     }
                     uploadForm.submit();
                 }
             };
         
             // --- 2. DROPZONE LOGIC ---
             const dropZone = document.getElementById('dropZone');
             const fileInput = document.getElementById('audit_file');
         
             if (dropZone) {
                 // Prevent default browser behavior for all drag events
                 ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                     dropZone.addEventListener(eventName, e => {
                         e.preventDefault();
                         e.stopPropagation();
                     }, false);
                 });
         
                 // Visual feedback
                 dropZone.addEventListener('dragover', () => dropZone.classList.add('border-indigo-500', 'bg-slate-800/80'));
                 dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-indigo-500', 'bg-slate-800/80'));
         
                 // The Drop
                 dropZone.addEventListener('drop', (e) => {
                     dropZone.classList.remove('border-indigo-500', 'bg-slate-800/80');
                     const dt = e.dataTransfer;
                     if (dt.files.length > 0) {
                         fileInput.files = dt.files;
                         window.triggerSentinelAnalysis();
                     }
                 });
             }
         
             // --- 3. PAGE INITIALIZATION (Hide Preloader) ---
             const hidePreloader = () => {
                 const preloader = document.getElementById('preloader');
                 if (preloader) {
                     preloader.style.opacity = '0';
                     setTimeout(() => { preloader.style.display = 'none'; }, 500);
                 }
             };
         
             // --- 4. DATATABLES INITIALIZATION ---
             if ($.fn.DataTable.isDataTable('#auditTable')) { 
                 $('#auditTable').DataTable().destroy(); 
             }
             
             $('#auditTable').DataTable({
                 pageLength: 25,
                 order: [[1, 'desc']],
                 dom: 'Bfrtip',
                 buttons: [{
                     extend: 'csv',
                     text: 'Export Audit Report (CSV)',
                     className: 'bg-indigo-600 text-white text-xs px-4 py-2 rounded-lg hover:bg-indigo-700 border-none'
                 }],
                 language: { search: "", searchPlaceholder: "Filter ledger..." }
             });
         
             // --- 5. CHARTS LOGIC ---
             const tempCanvas = document.getElementById('temporalChart');
             if (tempCanvas) {
                 new Chart(tempCanvas.getContext('2d'), {
                     type: 'line',
                     data: {
                         labels: <?php echo $labels ?? '[]'; ?>,
                         datasets: [
                             {
                                 label: 'Volume',
                                 data: <?php echo $txCounts ?? '[]'; ?>,
                                 borderColor: '#6366f1',
                                 backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                 fill: true,
                                 tension: 0.4,
                                 borderWidth: 3,
                                 pointRadius: 0
                             },
                             {
                                 label: 'Anomalies',
                                 data: <?php echo $alertCounts ?? '[]'; ?>,
                                 borderColor: '#f97316',
                                 backgroundColor: 'rgba(249, 115, 22, 0.2)',
                                 fill: true,
                                 tension: 0.4,
                                 borderWidth: 2,
                                 pointRadius: 3
                             }
                         ]
                     },
                     options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                 });
             }
         
             const catCanvas = document.getElementById('categoryChart');
             if (catCanvas) {
                 new Chart(catCanvas.getContext('2d'), {
                     type: 'bar',
                     data: {
                         labels: <?php echo $catLabels ?? '[]'; ?>,
                         datasets: [{
                             label: 'Anomaly Count',
                             data: <?php echo $catAlertData ?? '[]'; ?>,
                             backgroundColor: 'rgba(245, 158, 11, 0.8)',
                             borderColor: '#f59e0b',
                             borderWidth: 1,
                             borderRadius: 4
                         }]
                     },
                     options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                 });
             }
         
             // FINALLY: Hide the preloader now that everything is ready
             hidePreloader();
         });
      </script>   
   </body>
</html>
