import json
from pathlib import Path
import subprocess
if __name__ == '__main__':
    in_folder = '/mnt/hel.cp.jku.at/devel/python3/DSTM/out/session_seq/gen'
    out_folder = '/mnt/hel.cp.jku.at/devel/python3/DSTM/out/session_seq/sheets'
    sampling_methods = ['greedy_remi__crop', 'nucleus_remi__crop', 'typical_remi__crop']
    methods = [Path(f"{in_folder}/{method}") for method in sampling_methods]
    # methods.append('/mnt/hel.cp.jku.at/devel/python3/DSTM/out/session_seq/gen/orig')
    dirs = set([])
    mscore_conf = []
    for method in methods:
        for p in Path(f"{in_folder}/{method}").rglob('*.mid'):
            p_index = p.parents._parts.index('gen')
            exp = p.parents._parts[p_index+1:-1]
            out_folder_l = Path(out_folder, *exp)
            dirs.add(str(out_folder_l))
            out_file = out_folder_l.joinpath(f"{p.stem}.svg")
            if out_file.exists():
                continue
            mscore_conf.append({
                'in': str(p),
                'out': str(out_file),
            })
    for dir in dirs:
        Path(dir).mkdir(parents=True, exist_ok=True)
    with open('mscore_conf.json', 'w') as f:
        json.dump(mscore_conf, f)
    subprocess.run(['mscore', '-j', 'mscore_conf.json'])


# Print everything
# change color of buttons
# have borders for the experiment
# only more extreme
