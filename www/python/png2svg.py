import argparse, os, sys, subprocess
import os.path as op

SliceDirs = {'x':'saggital','y':'coronal','z':'axial'}

def argument_parser():
  """ Define the argument parser and return the parser object. """
  parser = argparse.ArgumentParser(
    description='description',
    formatter_class=argparse.RawTextHelpFormatter)
  parser.add_argument('-i','--png_src', type=str, help="Input PNG folder", required=True)
  parser.add_argument('-o','--svg_dest', type=str, help="Output SVG folder destination", required=True)
  parser.add_argument('-t','--curve_tolerance', type=str, help="Fitting curve tolerance", default = "2")
  parser.add_argument('-s','--line_tolerance', type=str, help="Fitting line tolerance", default="0.5")
  parser.add_argument('-c','--bg_color', type=str, help="Background color", default="")
  parser.add_argument('-d','--slice_dir', type=str, default='y', help="Slice direction: x=Saggital, y=Coronal, z=Axial")
  return parser

def parse_arguments(raw=None):
  """ Apply the argument parser. """
  args = argument_parser().parse_args(raw)

  """ Basic argument checking """
  if not op.exists(args.png_src):
    print 'PNG folder "{}" not found.'.format(args.png_src)
    exit(0)

  global SliceDirs
  if SliceDirs[args.slice_dir] is None:
    print 'Invalid slice direction "{}".'.format(args.slice_dir)
    exit(0)

  return args

def run(args):
  print('Input PNG Folder: '+args.png_src)
  try:
    global SliceDirs
    # Slice orientation is required to name the output svg folder only
    slice_dir_orientation = SliceDirs[args.slice_dir]

    pngFolder = args.png_src
    svgFolder = op.join(args.svg_dest,'svg')

    if not(op.exists(svgFolder)):
      os.makedirs(svgFolder)

    print 'Created output folder "{}".'.format(svgFolder)

    includedExtenstions=['png']
    fileNames=[fn for fn in os.listdir(pngFolder) if any([fn.endswith(ext) for ext in includedExtenstions])]

    # iterate over all png file names
    for pngFile in fileNames:
    
      baseName = op.splitext(pngFile)[0]
      svgFile = baseName+'.svg'
      inputFile = op.join(pngFolder,pngFile)
      outputFile =  op.join(svgFolder,svgFile)
      
      # generate and save svg with b2v
      if args.bg_color=="":
        ret2 = subprocess.call(["/home/dhruv/www/sba/python/b2v -i "+inputFile+" -o "+outputFile+" -t "+args.curve_tolerance+" -s "+args.line_tolerance], shell=True)
      else:
        ret2 = subprocess.call(["/home/dhruv/www/sba/python/b2v -i "+inputFile+" -o "+outputFile+" -t "+args.curve_tolerance+" -s "+args.line_tolerance+" -c \""+args.bg_color+"\""], shell=True)
      if ret2==1:
        print 'Error: Unsuccessful PNG to SVG conversion'
      else:
        print 'vector image saved to svg file "{}".'.format(svgFile)
  except:
    print "Unexpected error:", sys.exc_info()[0]
    raise

if __name__ == '__main__':
  args = parse_arguments()
  run(args)