using Microsoft.VisualBasic.FileIO; // à placer en haut du fichier
using System.Globalization; // à placer en haut du fichier

namespace airportFixer
{
    public partial class Form1 : Form
    {
        string type_de_piste_column_name = "Type de piste";
        string surface_column_name = "Surface";
        string longueur_column_name = "Longueur_de_piste";
        string aeroport_type_column_name = "type_aeroport";
        public Form1()
        {
            InitializeComponent();
        }
        string filePath = string.Empty;

        bool cancelled = false;

        private void button1_Click(object sender, EventArgs e)
        {
            //ouvre une boite de dialogue de sélection de fichier .csv

            OpenFileDialog openFileDialog = new OpenFileDialog();
            openFileDialog.Filter = "CSV files (*.csv)|*.csv|All files (*.*)|*.*";
            openFileDialog.Title = "Select a CSV file";
            if (openFileDialog.ShowDialog() == DialogResult.OK)
            {
                filePath = openFileDialog.FileName;
                btnFixIt.Enabled = true; // Active le bouton de traitement
                btnCheck.Enabled = true; // Active le bouton de traitement
            }
            else
            {
                MessageBox.Show("No file selected.");
            }

        }

        private bool askForUpdate(string name, ref string pistes, ref string longueurs, ref string surfaces, ref string url, string latitude, string longitude, string airportType)
        {
            askUpdateForm askUpdateForm = new askUpdateForm();
            askUpdateForm.Pistes = pistes;
            askUpdateForm.Longueurs = longueurs;
            askUpdateForm.Surfaces = surfaces;
            askUpdateForm.WIKIURL = url;
            askUpdateForm.name = name;
            askUpdateForm.airportType = airportType;

            if (double.TryParse(latitude, NumberStyles.Float, CultureInfo.InvariantCulture, out double lat))
            {
                askUpdateForm.latitude = lat;
            }
            if (double.TryParse(longitude, NumberStyles.Float, CultureInfo.InvariantCulture, out double lon))
            {
                askUpdateForm.longitude = lon;
            }

            if (askUpdateForm.ShowDialog() == DialogResult.OK)
            {
                // Récupère les valeurs saisies par l'utilisateur
                pistes = askUpdateForm.Pistes;
                longueurs = askUpdateForm.Longueurs;
                surfaces = askUpdateForm.Surfaces;
                url = askUpdateForm.WIKIURL;
                return true;
            }
            else
            {
                // L'utilisateur a annulé la saisie
                cancelled = true;
                return false;
            }
        }

        private void addItemToListView(string message, int lineNumber)
        {
            ListViewItem item = new ListViewItem(new string[] { message, lineNumber.ToString() });
            item.Tag = lineNumber;
            listView1.Items.Add(item);
        }

        private void button2_Click(object sender, EventArgs e)
        {
            listBox1.Items.Clear(); // Efface le contenu de la liste

            // Traite chaque ligne du fichier CSV
            int lineCount = 1;
            int typeDePisteIndex = -1;
            int longueurDePisteIndex = -1;
            int aiportTypeIndex = -1;
            int pisteIndex = -1;
            int urlIndex = -1;
            int latitudeIndex = -1;
            int longitudeIndex = -1;

            //crée un backup du fichier original
            string backupFilePath = System.IO.Path.Combine(System.IO.Path.GetDirectoryName(filePath), System.IO.Path.GetFileName(filePath) + ".bkp");
            if (File.Exists(backupFilePath))
            {
                File.Delete(backupFilePath); // Supprime le fichier de sauvegarde s'il existe déjà
            }
            File.Copy(filePath, backupFilePath); // Crée une copie de sauvegarde du fichier original

            // Lit le contenu du fichier CSV
            string[] lines = System.IO.File.ReadAllLines(backupFilePath);

            //crée un fichier pour ecrire les données corrigées
            //string outputFilePath = System.IO.Path.Combine(System.IO.Path.GetDirectoryName(filePath), "corrected_" + System.IO.Path.GetFileName(filePath));
            //crée un fichier pour ecrire les logs
            string logFilePath = System.IO.Path.Combine(System.IO.Path.GetDirectoryName(filePath), System.IO.Path.GetFileName(filePath) + ".log");
            // Ouvre le fichier de sortie en écriture
            if (File.Exists(filePath))
            {
                File.Delete(filePath); // Supprime le fichier s'il existe déjà
            }
            if (File.Exists(logFilePath))
            {
                File.Delete(logFilePath); // Supprime le fichier s'il existe déjà
            }
            using (StreamWriter logWriter = new StreamWriter(logFilePath))
            {
                logWriter.WriteLine("Log file for airport fixer");
                logWriter.WriteLine("File processed: " + filePath);
                logWriter.WriteLine("Output file: " + filePath);
                using (StreamWriter writer = new StreamWriter(filePath))
                {
                    // Écrit l'en-tête dans le fichier de sortie
                    writer.WriteLine(lines[0]);

                    foreach (string line in lines)
                    {
                        if (lineCount == 1)
                        {
                            //cherche le numero de la colonne "Type de piste", "Surface" et "Longueur_de_piste"
                            string[] headers = line.Split(',');
                            typeDePisteIndex = Array.IndexOf(headers, "\"Type_de_piste\"");
                            longueurDePisteIndex = Array.IndexOf(headers, "\"Longueur_de_piste\"");
                            aiportTypeIndex = Array.IndexOf(headers, "\"type_aeroport\"");
                            pisteIndex = Array.IndexOf(headers, "\"Piste\"");
                            urlIndex = Array.IndexOf(headers, "\"wikipedia_link\"");
                            latitudeIndex = Array.IndexOf(headers, "\"latitude_deg\"");
                            longitudeIndex = Array.IndexOf(headers, "\"longitude_deg\"");

                            if (typeDePisteIndex == -1 || longueurDePisteIndex == -1)
                            {
                                MessageBox.Show("Required columns not found in the CSV file.");
                                return;
                            }
                            lineCount++;
                            continue;
                        }
                        else
                        {
                            // Utilisation de TextFieldParser pour découper correctement la ligne CSV
                            using (TextFieldParser parser = new TextFieldParser(new StringReader(line)))
                            {
                                parser.HasFieldsEnclosedInQuotes = true;
                                parser.SetDelimiters(",");
                                try
                                {
                                    string[] fields = parser.ReadFields();

                                    // Nettoie chaque champ
                                    string[] cleanedFields = fields.Select(f => f.Trim('\"')).ToArray();

                                    string aeroportType = cleanedFields[aiportTypeIndex].Trim();

                                    string[] types = cleanedFields[typeDePisteIndex].TrimEnd('/').Split("/");
                                    if (cleanedFields[longueurDePisteIndex].EndsWith(" Ft"))
                                    {
                                        //remove the " Ft" from the end of the string
                                        cleanedFields[longueurDePisteIndex] = cleanedFields[longueurDePisteIndex].Substring(0, cleanedFields[longueurDePisteIndex].Length - 3);
                                    }
                                    string[] longueurs = cleanedFields[longueurDePisteIndex].TrimEnd('/').Split("/");
                                    string[] pistes = cleanedFields[pisteIndex].TrimEnd('/').Split(" ");

                                    string airportIdent = cleanedFields[2].Trim() + "(" + cleanedFields[0] + ")";

                                    int nbPistesFromTypes = types.Length;
                                    int nbPistesFromLongueurs = longueurs.Length;
                                    if (nbPistesFromLongueurs != nbPistesFromTypes)
                                    {
                                        logWriter.WriteLine("The number of surfaces and lengths does not match in line " + (lineCount));
                                        listBox1.Items.Add("The number of surfaces and lengths does not match in line " + (lineCount));
                                        addItemToListView("The number of surfaces and lengths does not match", lineCount);

                                        if (nbPistesFromTypes > nbPistesFromLongueurs)
                                        {
                                            if (nbPistesFromLongueurs == 1)
                                            {
                                                if (aeroportType == "heliport")
                                                {
                                                    if (nbPistesFromLongueurs == 1 && pistes.Length == 1)
                                                    {
                                                        // Si on a un heliport avec une seule piste, on peut supposer que la longueur est correcte
                                                        cleanedFields[typeDePisteIndex] = cleanedFields[typeDePisteIndex].Replace("/", " ");
                                                        logWriter.WriteLine("Fixed heliport with single runway in line " + (lineCount));
                                                        addItemToListView("Fixed heliport with single runway", lineCount);
                                                    }
                                                    else
                                                    {
                                                        if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                            ref cleanedFields[longueurDePisteIndex],
                                                            ref cleanedFields[typeDePisteIndex],
                                                            ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex], aeroportType
                                                            ))
                                                        {
                                                            // Si l'utilisateur annule la saisie, on ne fait rien
                                                            logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                            addItemToListView("User cancelled update", lineCount);

                                                        }
                                                        else
                                                        {
                                                            logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                            addItemToListView("User updated fields", lineCount);
                                                        }
                                                    }
                                                }
                                            }
                                            else
                                            {
                                                if (pistes.Length == nbPistesFromLongueurs)
                                                {
                                                    // Si le nombre de pistes correspond au nombre de longueurs, on peut supposer que les longueurs sont correctes
                                                    if (nbPistesFromLongueurs == 1)
                                                    {
                                                        cleanedFields[typeDePisteIndex] = cleanedFields[typeDePisteIndex].Replace("/", " ");
                                                    }
                                                    else
                                                    {
                                                        if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                            ref cleanedFields[longueurDePisteIndex],
                                                            ref cleanedFields[typeDePisteIndex],
                                                            ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex], aeroportType
                                                            ))
                                                        {
                                                            // Si l'utilisateur annule la saisie, on ne fait rien
                                                            logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                            addItemToListView("User cancelled update", lineCount);

                                                        }
                                                        else
                                                        {
                                                            logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                            addItemToListView("User updated fields", lineCount);
                                                        }
                                                    }
                                                }
                                                else
                                                {
                                                    string l = longueurs[0];
                                                    if ((l.Length % 4 == 0) && (l.Length > 4))
                                                    {
                                                        longueurs = new string[l.Length / 4];
                                                        for (int i = 0; i < l.Length / 4; i++)
                                                        {
                                                            longueurs[i] = l.Substring(i * 4, 4);
                                                        }
                                                        logWriter.WriteLine("Fixed lengths for airport in line " + (lineCount));
                                                        addItemToListView("Fixed lengths for airport", lineCount);
                                                        cleanedFields[longueurDePisteIndex] = string.Join("/", longueurs);
                                                    }
                                                    else
                                                    {
                                                        if (!cancelled)
                                                        {
                                                            int index = 0;
                                                            while (index < l.Length)
                                                            {
                                                                if (l[index] == '1')
                                                                {
                                                                    index += 5;
                                                                    if (index < l.Length)
                                                                        l = l.Insert(index, "/");
                                                                }
                                                                else
                                                                {
                                                                    index += 4;
                                                                    if (index < l.Length)
                                                                        l = l.Insert(index, "/");
                                                                }
                                                                index++;
                                                            }
                                                            cleanedFields[longueurDePisteIndex] = l;

                                                            if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                                ref cleanedFields[longueurDePisteIndex],
                                                                ref cleanedFields[typeDePisteIndex],
                                                                ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex], aeroportType))
                                                            {
                                                                // Si l'utilisateur annule la saisie, on ne fait rien
                                                                logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                                addItemToListView("User cancelled update", lineCount);

                                                            }
                                                            else
                                                            {
                                                                logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                                addItemToListView("User updated fields", lineCount);
                                                            }
                                                        }
                                                        else
                                                        {
                                                            logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                            addItemToListView("User cancelled update", lineCount);
                                                        }
                                                    }
                                                }
                                            }

                                        }
                                        else
                                        {
                                            if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                ref cleanedFields[longueurDePisteIndex],
                                                ref cleanedFields[typeDePisteIndex],
                                                ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex],
                                                            aeroportType))
                                            {
                                                // Si l'utilisateur annule la saisie, on ne fait rien
                                                logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                addItemToListView("User cancelled update", lineCount);

                                            }
                                            else
                                            {
                                                logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                addItemToListView("User updated fields", lineCount);
                                            }
                                        }
                                    }
                                    else
                                    {
                                        //le nombre de pistes est correct
                                        if (aeroportType != "heliport")
                                        {
                                            if (!cleanedFields[pisteIndex].Contains("-"))
                                            {
                                                //si on a des "/", on les remplace par des "-"
                                                string oldPiste = cleanedFields[pisteIndex];
                                                if (cleanedFields[pisteIndex].Contains("/"))
                                                {
                                                    cleanedFields[pisteIndex] = cleanedFields[pisteIndex].Replace("/", " - ");
                                                    logWriter.WriteLine("Fixed runway names for airport in line " + (lineCount));
                                                    //si on a plusieurs pistes, on doit avoir des "-" dans le nom des pistes
                                                    if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                    ref cleanedFields[longueurDePisteIndex],
                                                    ref cleanedFields[typeDePisteIndex],
                                                    ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex], aeroportType))
                                                    {
                                                        cleanedFields[pisteIndex] = oldPiste; //restore old value
                                                        // Si l'utilisateur annule la saisie, on ne fait rien
                                                        logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                        addItemToListView("User cancelled update", lineCount);
                                                    }
                                                    else
                                                    {
                                                        logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                        addItemToListView("User updated fields", lineCount);
                                                    }
                                                }
                                                else
                                                {
                                                    //on demande la mise a jour
                                                    if (cancelled || !askForUpdate(airportIdent, ref cleanedFields[pisteIndex],
                                                    ref cleanedFields[longueurDePisteIndex],
                                                    ref cleanedFields[typeDePisteIndex],
                                                    ref cleanedFields[urlIndex],
                                                            cleanedFields[latitudeIndex], cleanedFields[longitudeIndex], aeroportType))
                                                    {
                                                        // Si l'utilisateur annule la saisie, on ne fait rien
                                                        logWriter.WriteLine("User cancelled update for line " + (lineCount));
                                                        addItemToListView("User cancelled update", lineCount);
                                                    }
                                                    else
                                                    {
                                                        logWriter.WriteLine("User updated fields for line " + (lineCount));
                                                        addItemToListView("User updated fields", lineCount);
                                                    }
                                                }
                                            }
                                        }

                                        ////ici, on manque d'informations sur le type de pistes.
                                    }
                                    //ecrit les champs nettoyés dans le fichier de sortie avec des " autour de chaque champ
                                    for (int i = 0; i < cleanedFields.Length; i++)
                                    {
                                        cleanedFields[i] = "\"" + cleanedFields[i] + "\"";
                                    }
                                    writer.WriteLine(string.Join(",", cleanedFields));
                                }
                                catch (MalformedLineException ex)
                                {
                                    logWriter.WriteLine("Line " + lineCount + " is malformed: " + ex.Message);
                                    listBox1.Items.Add("Line " + lineCount + " is malformed: " + ex.Message);
                                    addItemToListView("Line is malformed", lineCount);
                                    writer.WriteLine(line); // Écrit la ligne originale dans le fichier de sortie
                                }

                                lineCount++;
                            }
                        }
                    }
                }
            }

        }

        private void btnCheck_Click(object sender, EventArgs e)
        {
            //Count the number fo columns in the csv file from the header line
            listBox1.Items.Clear(); // Efface le contenu de la liste
            listView1.Items.Clear(); // Efface le contenu de la liste
            int lineCount = 1;
            // Lit le contenu du fichier CSV
            string[] lines = System.IO.File.ReadAllLines(filePath);
            string[] headers = lines[0].Split(',');
            listBox1.Items.Add("Number of columns: " + headers.Length);

            //the check if each line has the same number of columns
            foreach (string line in lines)
            {
                //utilise TextFieldParser pour découper correctement la ligne CSV   
                using (TextFieldParser parser = new TextFieldParser(new StringReader(line)))
                {
                    parser.HasFieldsEnclosedInQuotes = true;
                    parser.SetDelimiters(",");
                    try
                    {
                        string[] fields = parser.ReadFields();
                        if (fields.Length != headers.Length)
                        {
                            listBox1.Items.Add("Line " + lineCount + " has " + fields.Length + " columns instead of " + headers.Length);
                            addItemToListView("Incorrect number of columns", lineCount);
                        }
                    }
                    catch (MalformedLineException ex)
                    {
                        listBox1.Items.Add("Line " + lineCount + " is malformed: " + ex.Message);
                        addItemToListView("Line is malformed", lineCount);
                    }
                }
                lineCount++;
            }

        }

        private void listView1_MouseDoubleClick(object sender, MouseEventArgs e)
        {
            //recupere le tag de l'item selectionné
            if (listView1.SelectedItems.Count > 0)
            {
                ListViewItem item = listView1.SelectedItems[0];
                if (item.Tag != null)
                {
                    int lineNumber = (int)item.Tag;
                }
            }
        }

        private void Form1_Load(object sender, EventArgs e)
        {
        }
    }
}
